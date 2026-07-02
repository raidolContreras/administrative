<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Envío de correo transaccional en texto plano.
 *
 * Decisiones:
 *  - mail() nativo: es lo único garantizado en hosting compartido cPanel sin
 *    dependencias ni credenciales SMTP. Si un cliente necesita SMTP real,
 *    este es el ÚNICO punto a tocar (o inyectar otra implementación).
 *  - En entorno local (APP_ENV=local) o sin MAIL_FROM configurado NO se intenta
 *    enviar: el mensaje se escribe en storage/logs/mail.log para poder copiar
 *    el enlace durante el desarrollo. En producción, si mail() falla, también
 *    se registra (el flujo del usuario no revela el fallo).
 *  - Texto plano: los correos de recuperación no necesitan HTML y así pasan
 *    mejor los filtros de spam de los hostings compartidos.
 */
final class Mailer
{
    public static function send(string $to, string $subject, string $body): bool
    {
        $from = trim((string) config('mail.from', ''));
        $fromName = trim((string) config('mail.from_name', '')) ?: (string) config('app.name');

        if (config('app.env') === 'local' || $from === '') {
            self::log($to, $subject, $body, 'no-enviado (entorno local o MAIL_FROM vacío)');
            return true;
        }

        // Nombre en Base64 (UTF-8 seguro) y remitente fijo del sistema: sin datos del usuario
        // en headers → sin inyección de cabeceras.
        $headers = 'From: =?UTF-8?B?' . base64_encode($fromName) . "?= <{$from}>\r\n"
            . "MIME-Version: 1.0\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n"
            . 'Content-Transfer-Encoding: 8bit';

        $ok = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', wordwrap($body, 78), $headers);
        if (!$ok) {
            self::log($to, $subject, $body, 'FALLO mail()');
        }
        return $ok;
    }

    private static function log(string $to, string $subject, string $body, string $status): void
    {
        $dir = storage_path('logs');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $entry = sprintf(
            "[%s] %s\nPara: %s\nAsunto: %s\n%s\n%s\n",
            now(),
            $status,
            $to,
            $subject,
            $body,
            str_repeat('-', 60)
        );
        @file_put_contents($dir . DIRECTORY_SEPARATOR . 'mail.log', $entry, FILE_APPEND | LOCK_EX);
    }
}
