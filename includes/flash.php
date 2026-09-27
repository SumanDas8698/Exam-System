<?php
/** flash.php — renders a one-time flash message if present in session. */
$__flash = get_flash();
if ($__flash):
    $__cls = $__flash['type'] === 'success' ? 'alert-success'
           : ($__flash['type'] === 'error' ? 'alert-danger'
           : ($__flash['type'] === 'warning' ? 'alert-warning' : 'alert-info'));
?>
<div class="alert <?= $__cls ?>" id="flashAlert">
    <span><?= $__flash['message'] ?></span>
    <button type="button" class="close-x" onclick="document.getElementById('flashAlert').remove()">&times;</button>
</div>
<?php endif; ?>
