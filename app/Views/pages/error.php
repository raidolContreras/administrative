<?php

use Core\View;

/*
 * Página de error autónoma (sin layout ni JS: debe funcionar aunque todo lo demás falle).
 * $status/$title/$detail son metadatos de infraestructura HTTP, no datos de negocio.
 */
$status = (int) ($status ?? 500);
$title = (string) ($title ?? 'Error');
$detail = (string) ($detail ?? '');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $status ?> — <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        body { font-family: ui-sans-serif, system-ui, -apple-system, sans-serif; background: #f1f5f9; color: #0f172a;
               display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .box { text-align: center; padding: 2rem; }
        .code { font-size: 5rem; font-weight: 800; color: #cbd5e1; line-height: 1; margin: 0; }
        h1 { font-size: 1.25rem; margin: 0.75rem 0 0.25rem; }
        p { color: #64748b; font-size: 0.9rem; max-width: 26rem; }
        a { display: inline-block; margin-top: 1.25rem; color: #0284c7; text-decoration: none; font-weight: 600; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="box">
        <p class="code"><?= $status ?></p>
        <h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
        <?php if ($detail !== ''): ?>
            <p><?= htmlspecialchars($detail, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <a href="<?= View::url('/') ?>">← Volver al inicio</a>
    </div>
</body>
</html>
