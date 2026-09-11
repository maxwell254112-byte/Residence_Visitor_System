<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_role('resident');

$resident = require_own_resident();
$pageTitle = 'Resident dashboard';
$currentNav = 'dashboard';
$visitors = list_visitors(['resident_id' => $resident['id']], 1);
$upcoming = list_visitors([
    'resident_id' => $resident['id'],
    'date_from' => today(),
    'status' => 'approved',
], 1);
$announcements = published_announcements('residents', 5);
$qr = active_resident_qr((int) $resident['id']);
$notifications = list_notifications((int) current_user()['id'], true, 5);

require ROOT_PATH . '/includes/layout/header.php';
?>
<div class="row g-3">
    <div class="col-lg-8">
        <div class="panel-card mb-3">
            <h2 class="h5">My information</h2>
            <div class="row g-3">
                <div class="col-md-6"><strong>Name</strong><div><?= e($resident['full_name']) ?></div></div>
                <div class="col-md-6"><strong>Code</strong><div><?= e($resident['resident_code']) ?></div></div>
                <div class="col-md-6"><strong>Unit</strong><div><?= e($resident['unit_number']) ?></div></div>
                <div class="col-md-6"><strong>Phone</strong><div><?= e($resident['phone']) ?></div></div>
                <div class="col-md-6"><strong>Status</strong><div><?= status_badge($resident['status']) ?></div></div>
            </div>
        </div>
        <div class="panel-card mb-3">
            <div class="d-flex justify-content-between">
                <h2 class="h5">My visitors</h2>
                <a href="<?= e(app_url('resident/visitors/index.php')) ?>">View all</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead><tr><th>Visitor</th><th>Date</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach (array_slice($visitors['rows'], 0, 6) as $row): ?>
                        <tr>
                            <td><?= e($row['visitor_name']) ?></td>
                            <td><?= e($row['visit_date']) ?></td>
                            <td><?= status_badge($row['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$visitors['rows']): ?>
                        <tr><td colspan="3" class="text-muted">No visitors yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="panel-card">
            <h2 class="h5">Announcements</h2>
            <?php foreach ($announcements as $item): ?>
                <div class="mb-3">
                    <strong><?= e($item['title']) ?></strong>
                    <div><?= nl2br(e($item['content'])) ?></div>
                </div>
            <?php endforeach; ?>
            <?php if (!$announcements): ?>
                <p class="text-muted mb-0">No announcements.</p>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="panel-card mb-3">
            <h2 class="h5">My QR</h2>
            <?php if ($qr): ?>
                <div class="qr-box" id="resident-qr" data-token="<?= e($qr['token']) ?>"></div>
            <?php else: ?>
                <p class="text-muted mb-0">No active QR. Contact the administrator.</p>
            <?php endif; ?>
            <a class="btn btn-outline-success btn-sm mt-3" href="<?= e(app_url('resident/qr.php')) ?>">Open QR page</a>
        </div>
        <div class="panel-card">
            <h2 class="h5">Unread notifications</h2>
            <?php foreach ($notifications as $item): ?>
                <div class="mb-2">
                    <strong><?= e($item['title']) ?></strong>
                    <div class="small"><?= e($item['message']) ?></div>
                </div>
            <?php endforeach; ?>
            <?php if (!$notifications): ?>
                <p class="text-muted mb-0">You're all caught up.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
(function () {
    var box = document.getElementById('resident-qr');
    if (box && typeof QRCode !== 'undefined') {
        new QRCode(box, { text: box.getAttribute('data-token'), width: 160, height: 160 });
    }
})();
</script>
<?php require ROOT_PATH . '/includes/layout/footer.php'; ?>
