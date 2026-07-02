<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\Attachment;
use Core\Auth;
use Core\Exceptions\HttpException;
use Core\Exceptions\ValidationException;
use Core\Request;
use Core\Response;
use Core\Validator;

/**
 * Uploads privados: viven en storage/uploads (fuera de public/) y se sirven
 * únicamente por este endpoint con autenticación.
 */
final class AttachmentController
{
    public function store(Request $request): Response
    {
        $meta = Validator::validate((array) $request->input(), [
            'entity_type' => 'nullable|string|max:60',
            'entity_id' => 'nullable|string|max:40',
        ]);

        $file = $request->file('file');
        if ($file === null || $file['error'] !== UPLOAD_ERR_OK) {
            throw new ValidationException(['file' => ['Selecciona un archivo válido.']]);
        }
        if ($file['size'] > (int) config('uploads.max_bytes')) {
            $mb = round(((int) config('uploads.max_bytes')) / 1048576, 1);
            throw new ValidationException(['file' => ["El archivo no debe exceder {$mb} MB."]]);
        }

        $allowed = (array) config('uploads.allowed');
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!isset($allowed[$ext]) || !in_array($mime, $allowed[$ext], true)) {
            throw new ValidationException(['file' => ['Tipo de archivo no permitido: ' . implode(', ', array_keys($allowed)) . '.']]);
        }

        // Nombre regenerado (nunca el del cliente) en subcarpeta por fecha
        $relative = date('Y/m') . '/' . bin2hex(random_bytes(16)) . '.' . $ext;
        $absolute = storage_path('uploads/' . $relative);
        if (!is_dir(dirname($absolute))) {
            mkdir(dirname($absolute), 0775, true);
        }
        if (!move_uploaded_file($file['tmp_name'], $absolute)) {
            return Response::error(500, 'UPLOAD_FAILED', 'No se pudo guardar el archivo.');
        }

        $attachment = Attachment::create([
            'disk' => 'private',
            'path' => $relative,
            'original_name' => mb_substr($file['name'], 0, 190),
            'mime' => $mime,
            'size' => $file['size'],
            'entity_type' => $meta['entity_type'] ?? null,
            'entity_id' => $meta['entity_id'] ?? null,
            'uploaded_by' => Auth::id(),
        ]);

        return Response::json($attachment, 201);
    }

    public function download(Request $request): Response
    {
        $attachment = Attachment::findOrFail((int) $request->param('id'));
        $path = Attachment::absolutePath($attachment);
        if (!is_file($path)) {
            throw HttpException::notFound('El archivo ya no existe en el servidor.');
        }
        return Response::file($path, (string) $attachment['original_name'], (string) $attachment['mime']);
    }

    public function destroy(Request $request): Response
    {
        $attachment = Attachment::findOrFail((int) $request->param('id'));
        if (Auth::role() !== 'admin' && (int) ($attachment['uploaded_by'] ?? 0) !== (int) Auth::id()) {
            throw HttpException::forbidden('Solo el administrador o quien subió el archivo puede eliminarlo.');
        }
        @unlink(Attachment::absolutePath($attachment));
        Attachment::delete((int) $attachment['id']);
        return Response::noContent();
    }
}
