<?php

declare(strict_types=1);

namespace Core;

final class Response
{
    private const CSP = "default-src 'self'; script-src 'self' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; "
        . "img-src 'self' data: blob:; font-src 'self'; connect-src 'self'; frame-ancestors 'self'; "
        . "base-uri 'self'; form-action 'self'";

    private function __construct(
        private int $status,
        private array $headers,
        private string $body,
    ) {
    }

    /** Respuesta de éxito con el contrato estándar {success, data, meta} */
    public static function json(mixed $data = null, int $status = 200, ?array $meta = null): self
    {
        $payload = ['success' => true, 'data' => $data];
        if ($meta !== null) {
            $payload['meta'] = $meta;
        }
        return new self($status, self::jsonHeaders(), self::encode($payload));
    }

    /** Respuesta de error con el contrato estándar {success:false, error:{code,message,details}} */
    public static function error(int $status, string $code, string $message, mixed $details = null): self
    {
        $error = ['code' => $code, 'message' => $message];
        if ($details !== null) {
            $error['details'] = $details;
        }
        return new self($status, self::jsonHeaders(), self::encode(['success' => false, 'error' => $error]));
    }

    public static function noContent(): self
    {
        return new self(204, ['Cache-Control' => 'no-store'], '');
    }

    public static function html(string $body, int $status = 200): self
    {
        return new self($status, [
            'Content-Type' => 'text/html; charset=utf-8',
            'Content-Security-Policy' => self::CSP,
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'same-origin',
            'Cache-Control' => 'no-cache',
        ], $body);
    }

    public static function redirect(string $to, int $status = 302): self
    {
        return new self($status, ['Location' => $to], '');
    }

    /** Descarga/stream de un archivo local (uploads privados) */
    public static function file(string $path, string $downloadName, string $mime, bool $inline = false): self
    {
        $disposition = ($inline ? 'inline' : 'attachment')
            . "; filename=\"" . str_replace(['"', "\r", "\n"], '', $downloadName) . '"';
        return new self(200, [
            'Content-Type' => $mime,
            'Content-Disposition' => $disposition,
            'Content-Length' => (string) filesize($path),
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-store',
            '__file' => $path,
        ], '');
    }

    public function withHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            $file = null;
            foreach ($this->headers as $name => $value) {
                if ($name === '__file') {
                    $file = $value;
                    continue;
                }
                header($name . ': ' . $value);
            }
            if ($file !== null) {
                Session::writeClose();
                readfile($file);
                return;
            }
        }
        echo $this->body;
    }

    private static function jsonHeaders(): array
    {
        return [
            'Content-Type' => 'application/json; charset=utf-8',
            'Cache-Control' => 'no-store',
            'X-Content-Type-Options' => 'nosniff',
        ];
    }

    private static function encode(array $payload): string
    {
        return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{"success":false}';
    }
}
