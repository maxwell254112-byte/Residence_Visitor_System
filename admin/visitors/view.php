<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/init.php';
require_permission('visitors.view');

$visitor = find_visitor(get_int('id'));
if (!$visitor) {
    flash_set('danger', 'Visitor not found.');
    redirect('admin/visitors/index.php');
}

$pageTitle = 'Visitor details';
$currentNav = 'visitors';
$actor = current_user();

if (is_post()) {
    require_csrf();
    $action = post_string('action');
    if ($action === 'reject' && can('visitors.edit')) {
        $reason = post_string('rejection_reason');
        if ($reason === '') {
            flash_set('danger', 'A rejection reason is required.');
        } else {
            reject_visitor((int) $visitor['id'], $reason, (int) $actor['id']);
            flash_set('success', 'Visitor rejected.');
        }
    } elseif ($action === 'checkout' && can('visitors.checkout')) {
        $result = checkout_visitor((int) $visitor['id'], (int) $actor['id']);
        flash_set($result['ok'] ? 'success' : 'danger', $result['message']);
    }
    redirect('admin/visitors/view.php?id=' . (int) $visitor['id']);
}

require ROOT_PATH . '/includes/layout/header.php';
?>
<div class="row g-3">
    <div class="col-lg-8">
        <div class="panel-card">
            <div class="d-flex justify-content-between">
                <h2 class="h4"><?= e($visitor['visitor_name']) ?></h2>
                <?= status_badge($visitor['status']) ?>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-md-6"><strong>Host</strong><div><?= e($visitor['resident_name']) ?> · <?= e($visitor['unit_number']) ?></div></div>
                <div class="col-md-6"><strong>Phone</strong><div><?= e($visitor['phone']) ?></div></div>
                <div class="col-md-6"><strong>Car plate</strong><div><?= e((string) $visitor['car_plate'] ?: '—') ?></div></div>
                <div class="col-md-6"><strong>Purpose</strong><div><?= e($visitor['purpose']) ?></div></div>
                <div class="col-md-6"><strong>Visit date</strong><div><?= e($visitor['visit_date']) ?></div></div>
                <div class="col-md-6"><strong>Valid until</strong><div><?= e($visitor['valid_until']) ?></div></div>
                <div class="col-md-6"><strong>Approved by</strong><div><?= e((string) $visitor['approved_by_name'] ?: '—') ?></div></div>
                <div class="col-md-6"><strong>Check-in</strong><div><?= e((string) $visitor['checked_in_at'] ?: '—') ?></div></div>
                <div class="col-md-6"><strong>Check-out</strong><div><?= e((string) $visitor['checked_out_at'] ?: '—') ?></div></div>
                <?php if ($visitor['rejection_reason']): ?>
                    <div class="col-12"><strong>Rejection reason</strong><div><?= e($visitor['rejection_reason']) ?></div></div>
                <?php endif; ?>
            </div>
            <div class="mt-3 d-flex flex-wrap gap-2">
                <?php if (can('visitors.edit') && in_array($visitor['status'], ['pending', 'approved'], true)): ?>
                    <a class="btn btn-outline-success" href="<?= e(app_url('admin/visitors/edit.php?id=' . (int) $visitor['id'])) ?>">Edit</a>
                    <form method="post" class="d-flex gap-2">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="reject">
                        <input class="form-control" name="rejection_reason" placeholder="Rejection reason" required>
                        <button class="btn btn-outline-danger" type="submit">Reject</button>
                    </form>
                <?php endif; ?>
                <?php if (can('visitors.checkout') && $visitor['status'] === 'checked_in'): ?>
                    <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="checkout">
                        <button class="btn btn-success" type="submit">Check out</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="panel-card">
            <h2 class="h5">Visitor QR</h2>
            <p class="text-muted">Contains only the invitation token.</p>
            <div class="qr-box">
                <div id="visitor-qr" data-token="<?= e($visitor['qr_token']) ?>"></div>
            </div>
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
