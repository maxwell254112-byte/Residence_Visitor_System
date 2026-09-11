<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/init.php';
require_permission('residents.view');

$resident = find_resident(get_int('id'));
if (!$resident) {
    flash_set('danger', 'Resident not found.');
    redirect('admin/residents/index.php');
}

$pageTitle = 'Resident profile';
$currentNav = 'residents';
$actor = current_user();
$qr = active_resident_qr((int) $resident['id']);

if (is_post() && can('residents.edit')) {
    require_csrf();
    $action = post_string('action');
    if ($action === 'generate_qr') {
        generate_resident_qr((int) $resident['id'], (int) $actor['id']);
        flash_set('success', 'A new resident QR token was generated.');
    } elseif ($action === 'revoke_qr') {
        revoke_resident_qr((int) $resident['id'], (int) $actor['id']);
        flash_set('success', 'The resident QR token was revoked.');
    }
    redirect('admin/residents/view.php?id=' . (int) $resident['id']);
}

require ROOT_PATH . '/includes/layout/header.php';
?>
<div class="row g-3">
    <div class="col-lg-8">
        <div class="panel-card">
            <div class="d-flex justify-content-between">
                <div>
                    <h2 class="h4 mb-1"><?= e($resident['full_name']) ?></h2>
                    <div class="text-muted"><?= e($resident['resident_code']) ?> · Unit <?= e($resident['unit_number']) ?></div>
                </div>
                <div><?= status_badge($resident['status']) ?></div>
            </div>
            <hr>
            <div class="row g-3">
                <div class="col-md-6"><strong>Gender</strong><div><?= e(ucfirst($resident['gender'])) ?></div></div>
                <div class="col-md-6"><strong>Phone</strong><div><?= e($resident['phone']) ?></div></div>
                <div class="col-md-6"><strong>Email</strong><div><?= e((string) $resident['email'] ?: '—') ?></div></div>
                <div class="col-md-6"><strong>Account</strong><div><?= e((string) $resident['username'] ?: 'Not linked') ?></div></div>
                <div class="col-12"><strong>Address</strong><div><?= e((string) $resident['address'] ?: '—') ?></div></div>
                <div class="col-md-6"><strong>Emergency contact</strong><div><?= e((string) $resident['emergency_contact'] ?: '—') ?></div></div>
                <div class="col-md-6"><strong>Emergency phone</strong><div><?= e((string) $resident['emergency_contact_phone'] ?: '—') ?></div></div>
            </div>
            <div class="mt-3">
                <?php if (can('residents.edit')): ?>
                    <a class="btn btn-success" href="<?= e(app_url('admin/residents/edit.php?id=' . (int) $resident['id'])) ?>">Edit</a>
                <?php endif; ?>
                <a class="btn btn-outline-secondary" href="<?= e(app_url('admin/residents/history.php?id=' . (int) $resident['id'])) ?>">History</a>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="panel-card">
            <h2 class="h5">Resident QR</h2>
            <p class="text-muted">The QR contains only a random token. Personal details are never encoded.</p>
            <?php if ($qr): ?>
                <div class="qr-box mb-3">
                    <div id="resident-qr" data-token="<?= e($qr['token']) ?>"></div>
                </div>
                <div class="small text-muted mb-2">Created <?= e($qr['created_at']) ?></div>
            <?php else: ?>
                <p class="text-muted">No active QR token.</p>
            <?php endif; ?>
            <?php if (can('residents.edit')): ?>
                <form method="post" class="d-flex gap-2">
                    <?= csrf_field() ?>
                    <button class="btn btn-outline-success btn-sm" name="action" value="generate_qr" type="submit">Generate</button>
                    <?php if ($qr): ?>
                        <button class="btn btn-outline-danger btn-sm" name="action" value="revoke_qr" type="submit">Revoke</button>
                    <?php endif; ?>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
(function () {
    var box = document.getElementById('resident-qr');
    if (box && typeof QRCode !== 'undefined') {
        new QRCode(box, { text: box.getAttribute('data-token'), width: 180, height: 180 });
    }
})();
</script>
<?php require ROOT_PATH . '/includes/layout/footer.php'; ?>
