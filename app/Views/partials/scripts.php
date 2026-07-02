<?php

use Core\View;

/*
 * Orden de carga (todos defer → se ejecutan en orden de documento):
 * api.js → store/app → componentes → script de página → Alpine AL FINAL
 * (los listeners de alpine:init deben registrarse antes de que Alpine arranque).
 * Convención: si existe assets/js/pages/<vista>.js se incluye solo; los módulos
 * pueden indicar su script con el default 'script' de la ruta.
 */
?>
<script src="<?= View::asset('js/api.js') ?>" defer></script>
<script src="<?= View::asset('js/app.js') ?>" defer></script>
<script src="<?= View::asset('js/components/toast.js') ?>" defer></script>
<script src="<?= View::asset('js/components/confirmDialog.js') ?>" defer></script>
<script src="<?= View::asset('js/components/dataTable.js') ?>" defer></script>
<script src="<?= View::asset('js/components/formModal.js') ?>" defer></script>
<?php $autoScript = 'js/pages/' . ($viewName ?? '') . '.js'; ?>
<?php if (!empty($pageScript)): ?>
<script src="<?= View::asset((string) $pageScript) ?>" defer></script>
<?php elseif (($viewName ?? '') !== '' && is_file(base_path('public/assets/' . $autoScript))): ?>
<script src="<?= View::asset($autoScript) ?>" defer></script>
<?php endif; ?>
<script src="<?= View::asset('js/vendor/alpine.min.js') ?>" defer></script>
