<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\Setting;
use Core\Exceptions\ValidationException;
use Core\Request;
use Core\Response;

final class SettingController
{
    private const LOGO_TYPES = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];

    public function index(Request $request): Response
    {
        return Response::json(Setting::all('key'));
    }

    /** Actualización masiva {values: {clave: valor}} — solo claves existentes */
    public function update(Request $request): Response
    {
        $values = $request->input('values');
        if (!is_array($values) || $values === []) {
            throw new ValidationException(['values' => ['Envía un objeto {clave: valor}.']]);
        }
        foreach ($values as $key => $value) {
            if (Setting::firstWhere('key', (string) $key) !== null) {
                Setting::set((string) $key, is_scalar($value) || $value === null ? $value : json_encode($value));
            }
        }
        return Response::json(Setting::all('key'));
    }

    /** Logo del negocio: público (aparece en el login), validado con finfo, nombre regenerado */
    public function uploadLogo(Request $request): Response
    {
        $file = $request->file('logo');
        if ($file === null || $file['error'] !== UPLOAD_ERR_OK) {
            throw new ValidationException(['logo' => ['Selecciona una imagen válida.']]);
        }
        if ($file['size'] > 2 * 1024 * 1024) {
            throw new ValidationException(['logo' => ['La imagen no debe exceder 2 MB.']]);
        }
        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!isset(self::LOGO_TYPES[$mime])) {
            throw new ValidationException(['logo' => ['Formato no permitido. Usa PNG, JPG o WebP.']]);
        }

        $name = 'logo-' . bin2hex(random_bytes(8)) . '.' . self::LOGO_TYPES[$mime];
        $dir = base_path('public/uploads');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        if (!move_uploaded_file($file['tmp_name'], $dir . DIRECTORY_SEPARATOR . $name)) {
            return Response::error(500, 'UPLOAD_FAILED', 'No se pudo guardar la imagen.');
        }

        // Borrar el logo anterior (nombre regenerado → sin colisiones)
        $previous = (string) Setting::get('logo_path', '');
        if ($previous !== '' && str_starts_with($previous, '/uploads/')) {
            @unlink(base_path('public' . $previous));
        }

        Setting::set('logo_path', '/uploads/' . $name);
        return Response::json(['logo_path' => '/uploads/' . $name]);
    }
}
