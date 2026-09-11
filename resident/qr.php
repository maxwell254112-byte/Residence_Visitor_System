<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_role('resident');

$resident = require_own_resident();
$pageTitle = 'My QR';
$currentNav = 'qr';
$qr = active_resident_qr((int) $resident['id']);

require ROOT_PATH . '/includes/layout/header.php';
?>
<div class="panel-card" style="max-width: 480px;">
    <p class="text-muted">This QR contains only a random token. It does not include your name, phone, unit, or address.</p>
    <?php if ($qr): ?>
        <div class="qr-box" id="resident-qr" data-token="<?= e($qr['token']) ?>"></div>
        <div class="small text-muted mt-3">Issued <?= e($qr['created_at']) ?></div>
    <?php else: ?>
        <div class="alert alert-warning">No active QR token is available. Please contact the administrator.</div>
    <?php endif; ?>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
(function () {
    var box = document.getElementById('resident-qr');
    if (box && typeof QRCode !== 'undefined') {
        new QRCode(box, { text: box.getAttribute('data-token'), width: 200, height: 200 });
    }
})();
</script>
<?php require ROOT_PATH . '/includes/layout/footer.php'; ?>
