<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_permission('visitors.view');

$visitor = find_visitor(get_int('id'));
if (!$visitor) {
    flash_set('danger', 'Visitor not found.');
    redirect('security/dashboard.php');
}

$pageTitle = 'Visitor QR';
$currentNav = 'dashboard';

require ROOT_PATH . '/includes/layout/header.php';
?>
<div class="row g-3">
    <div class="col-lg-6">
        <div class="panel-card">
            <div class="d-flex justify-content-between">
                <h2 class="h4"><?= e($visitor['visitor_name']) ?></h2>
                <?= status_badge($visitor['status']) ?>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-md-6"><strong>Host</strong><div><?= e($visitor['resident_name']) ?> · <?= e($visitor['unit_number']) ?></div></div>
                <div class="col-md-6"><strong>Purpose</strong><div><?= e($visitor['purpose']) ?></div></div>
                <div class="col-md-6"><strong>Visit date</strong><div><?= e($visitor['visit_date']) ?></div></div>
                <div class="col-md-6"><strong>Valid until</strong><div><?= e($visitor['valid_until']) ?></div></div>
            </div>
            <a class="btn btn-outline-secondary mt-3" href="<?= e(app_url('security/dashboard.php')) ?>">Back</a>
            <a class="btn btn-success mt-3" href="<?= e(app_url('security/scan.php')) ?>">Open scanner</a>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="panel-card">
            <h2 class="h5">Visitor QR</h2>
            <p class="text-muted">Scan this QR on the security scanner page. Do not scan the resident QR.</p>
            <div class="qr-box" id="visitor-qr" data-token="<?= e($visitor['qr_token']) ?>"></div>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
(function () {
    var box = document.getElementById('visitor-qr');
    if (box && typeof QRCode !== 'undefined') {
        new QRCode(box, { text: box.getAttribute('data-token'), width: 200, height: 200 });
    }
})();
</script>
<?php require ROOT_PATH . '/includes/layout/footer.php'; ?>
