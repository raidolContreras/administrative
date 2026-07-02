<?php

declare(strict_types=1);

namespace Core;

use Core\Exceptions\ValidationException;

/**
 * Validador declarativo. Reglas por campo como 'required|email|max:190' o array.
 * Devuelve SOLO los campos con regla (whitelist) con tipos normalizados.
 * Lanza ValidationException → 422 con errores por campo (mensajes es-MX).
 */
final class Validator
{
    /**
     * @param array $context p.ej. ['id' => 5] para que unique ignore el propio registro
     * @return array datos validados y normalizados
     */
    public static function validate(array $data, array $rules, array $context = []): array
    {
        $clean = [];
        $errors = [];

        foreach ($rules as $field => $ruleSpec) {
            $ruleList = is_array($ruleSpec) ? $ruleSpec : explode('|', $ruleSpec);
            $value = $data[$field] ?? null;

            if (is_string($value) && !str_contains($field, 'password')) {
                $value = trim($value);
            }
            $isEmpty = $value === null || $value === '';
            $isNullable = in_array('nullable', $ruleList, true);
            $isRequired = in_array('required', $ruleList, true);

            if ($isEmpty) {
                if ($isRequired) {
                    $errors[$field][] = 'Este campo es obligatorio.';
                } elseif ($isNullable) {
                    $clean[$field] = null;
                }
                continue;
            }

            $fieldErrors = [];
            foreach ($ruleList as $rule) {
                if ($rule === 'required' || $rule === 'nullable' || $rule === '') {
                    continue;
                }
                [$name, $params] = array_pad(explode(':', $rule, 2), 2, '');
                $error = self::apply($name, $params, $value, $context, $ruleList);
                if ($error !== null) {
                    $fieldErrors[] = $error;
                    break; // primer error por campo es suficiente
                }
            }

            if ($fieldErrors !== []) {
                $errors[$field] = $fieldErrors;
            } else {
                $clean[$field] = $value;
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
        return $clean;
    }

    /** Aplica una regla; devuelve mensaje de error o null. $value se pasa por referencia para normalizar tipos. */
    private static function apply(string $name, string $params, mixed &$value, array $context, array $ruleList): ?string
    {
        // min/max comparan por valor solo en campos numéricos; en texto comparan longitud
        $isNumericField = is_int($value) || is_float($value)
            || in_array('int', $ruleList, true) || in_array('numeric', $ruleList, true);

        switch ($name) {
            case 'string':
                return is_string($value) ? null : 'Debe ser texto.';

            case 'email':
                return (is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL) !== false)
                    ? null : 'Debe ser un correo electrónico válido.';

            case 'int':
                if (is_int($value) || (is_string($value) && preg_match('/^-?\d+$/', $value))) {
                    $value = (int) $value;
                    return null;
                }
                return 'Debe ser un número entero.';

            case 'numeric':
                return is_numeric($value) ? null : 'Debe ser un número.';

            case 'bool':
                $parsed = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
                if ($parsed === null) {
                    return 'Debe ser verdadero o falso.';
                }
                $value = $parsed ? 1 : 0; // normalizado para TINYINT
                return null;

            case 'min':
                if ($isNumericField) {
                    return (is_numeric($value) && (float) $value >= (float) $params)
                        ? null : "Debe ser al menos {$params}.";
                }
                return mb_strlen((string) $value) >= (int) $params ? null : "Debe tener al menos {$params} caracteres.";

            case 'max':
                if ($isNumericField) {
                    return (is_numeric($value) && (float) $value <= (float) $params)
                        ? null : "No debe ser mayor a {$params}.";
                }
                return mb_strlen((string) $value) <= (int) $params ? null : "No debe exceder {$params} caracteres.";

            case 'in':
                $allowed = explode(',', $params);
                return in_array((string) $value, $allowed, true) ? null : 'El valor seleccionado no es válido.';

            case 'date':
                $dt = \DateTime::createFromFormat('Y-m-d', (string) $value);
                return ($dt !== false && $dt->format('Y-m-d') === $value) ? null : 'Debe ser una fecha válida (AAAA-MM-DD).';

            case 'datetime':
                foreach (['Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d\TH:i:s', 'Y-m-d\TH:i'] as $format) {
                    $dt = \DateTime::createFromFormat($format, (string) $value);
                    if ($dt !== false && $dt->format($format) === $value) {
                        $value = $dt->format('Y-m-d H:i:s');
                        return null;
                    }
                }
                return 'Debe ser una fecha y hora válidas.';

            case 'unique': {
                [$table, $column] = array_pad(explode(',', $params), 2, '');
                $sql = 'SELECT COUNT(*) FROM ' . Database::ident($table) . ' WHERE ' . Database::ident($column) . ' = ?';
                $bind = [$value];
                if (isset($context['id'])) {
                    $sql .= ' AND id != ?';
                    $bind[] = $context['id'];
                }
                return ((int) Database::scalar($sql, $bind)) === 0 ? null : 'Ya existe un registro con este valor.';
            }

            case 'exists': {
                [$table, $column] = array_pad(explode(',', $params), 2, '');
                $found = (int) Database::scalar(
                    'SELECT COUNT(*) FROM ' . Database::ident($table) . ' WHERE ' . Database::ident($column) . ' = ?',
                    [$value]
                );
                return $found > 0 ? null : 'La referencia seleccionada no existe.';
            }

            case 'regex':
                return preg_match($params, (string) $value) === 1 ? null : 'El formato no es válido.';

            default:
                throw new \LogicException("Regla de validación desconocida: {$name}");
        }
    }
}
