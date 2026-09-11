<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/init.php';
require_role('resident');

$resident = require_own_resident();
$visitor = find_visitor(get_int('id'));
if (!$visitor || (int) $visitor['resident_id'] !== (int) $resident['id']) {
    flash_set('danger', 'Visitor not found.');
    redirect('resident/visitors/index.php');
}

$pageTitle = 'Visitor details';
$currentNav = 'visitors';

require ROOT_PATH . '/includes/layout/header.php';
?>
<div class="row g-3">
    <div class="col-lg-7">
        <div class="panel-card">
            <div class="d-flex justify-content-between">
                <h2 class="h4"><?= e($visitor['visitor_name']) ?></h2>
                <?= status_badge($visitor['status']) ?>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-md-6"><strong>Phone</strong><div><?= e($visitor['phone']) ?></div></div>
                <div class="col-md-6"><strong>Car plate</strong><div><?= e((string) $visitor['car_plate'] ?: '—') ?></div></div>
                <div class="col-md-6"><strong>Purpose</strong><div><?= e($visitor['purpose']) ?></div></div>
                <div class="col-md-6"><strong>Visit date</strong><div><?= e($visitor['visit_date']) ?></div></div>
                <div class="col-md-6"><strong>Valid until</strong><div><?= e($visitor['valid_until']) ?></div></div>
                <div class="col-md-6"><strong>Check-in</strong><div><?= e((string) $visitor['checked_in_at'] ?: '—') ?></div></div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="panel-card">
            <h2 class="h5">Visitor QR</h2>
            <p class="text-muted">Ask your visitor to present this QR at the guard house.</p>
            <div class="qr-box" id="visitor-qr" data-token="<?= e($visitor['qr_token']) ?>"></div>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
(function () {
    var box = document.getElementById('visitor-qr');
    if (box && typeof QRCode !== 'undefined') {
        new QRCode(box, { text: box.getAttribute('data-token'), width: 180, height: 180 });
    }
})();
</script>
<?php require ROOT_PATH . '/includes/layout/footer.php'; ?>
