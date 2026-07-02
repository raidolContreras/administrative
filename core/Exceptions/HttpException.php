<?php

declare(strict_types=1);

namespace Core\Exceptions;

class HttpException extends \RuntimeException
{
    public function __construct(
        public readonly int $status,
        public readonly string $errorCode,
        string $message,
        public readonly mixed $details = null,
    ) {
        parent::__construct($message);
    }

    public static function notFound(string $message = 'Recurso no encontrado.'): self
    {
        return new self(404, 'NOT_FOUND', $message);
    }

    public static function methodNotAllowed(array $allowed): self
    {
        return new self(405, 'METHOD_NOT_ALLOWED', 'Método no permitido.', ['allowed' => $allowed]);
    }

    public static function unauthorized(string $message = 'Debes iniciar sesión.'): self
    {
        return new self(401, 'UNAUTHENTICATED', $message);
    }

    public static function forbidden(string $message = 'No tienes permisos para esta acción.', string $code = 'FORBIDDEN'): self
    {
        return new self(403, $code, $message);
    }

    public static function tooManyRequests(string $message, int $retryAfterSeconds): self
    {
        return new self(429, 'TOO_MANY_ATTEMPTS', $message, ['retry_after' => $retryAfterSeconds]);
    }

    public static function conflict(string $message, string $code = 'CONFLICT'): self
    {
        return new self(409, $code, $message);
    }
}
