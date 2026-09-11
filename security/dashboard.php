<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_role('security', 'admin');
require_permission('visitors.view');

$pageTitle = 'Security dashboard';
$currentNav = 'dashboard';
$actor = current_user();
$counts = visitor_counts();
$inside = list_visitors(['inside' => 1], 1);
$todayVisitors = list_visitors(['date_from' => today(), 'date_to' => today()], 1);
$scans = recent_scans(8);
$announcements = published_announcements('security', 4);

if (is_post()) {
    require_csrf();
    if (post_string('action') === 'checkout' && can('visitors.checkout')) {
        $result = checkout_visitor(post_int('id'), (int) $actor['id']);
        flash_set($result['ok'] ? 'success' : 'danger', $result['message']);
        redirect('security/dashboard.php');
    }
}

require ROOT_PATH . '/includes/layout/header.php';
?>
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3"><div class="stat-card"><div class="label">Inside now</div><div class="value"><?= (int) $counts['inside'] ?></div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card"><div class="label">Today's visitors</div><div class="value"><?= (int) $counts['today'] ?></div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card"><div class="label">Pending / approved</div><div class="value"><?= (int) $counts['pending'] ?></div></div></div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="label">QR scanner</div>
            <a class="btn btn-success mt-2" href="<?= e(app_url('security/scan.php')) ?>">Open scanner</a>
        </div>
    </div>
</div>
<div class="row g-3">
    <div class="col-lg-7">
        <div class="panel-card mb-3" id="inside">
            <h2 class="h5">Visitors currently inside</h2>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead><tr><th>Visitor</th><th>Host</th><th>In since</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($inside['rows'] as $row): ?>
                        <tr>
                            <td><?= e($row['visitor_name']) ?></td>
                            <td><?= e($row['resident_name']) ?> · <?= e($row['unit_number']) ?></td>
                            <td><?= e((string) $row['checked_in_at']) ?></td>
                            <td>
                                <?php if (can('visitors.checkout')): ?>
                                    <form method="post">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="checkout">
                                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                        <button class="btn btn-sm btn-outline-success" type="submit">Check out</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$inside['rows']): ?>
                        <tr><td colspan="4" class="text-muted">No visitors are currently inside.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="panel-card">
            <h2 class="h5">Today's visitors</h2>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead><tr><th>Visitor</th><th>Host</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($todayVisitors['rows'] as $row): ?>
                        <tr>
                            <td><?= e($row['visitor_name']) ?></td>
                            <td><?= e($row['resident_name']) ?></td>
                            <td><?= status_badge($row['status']) ?></td>
                            <td>
                                <a class="btn btn-sm btn-outline-secondary" href="<?= e(app_url('security/visitor.php?id=' . (int) $row['id'])) ?>">Visitor QR</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="panel-card mb-3">
            <h2 class="h5">Recent scans</h2>
            <?php foreach ($scans as $scan): ?>
                <div class="mb-2">
                    <?= status_badge($scan['result'] === 'success' ? 'approved' : 'rejected') ?>
                    <?= e((string) $scan['visitor_name'] ?: 'Unknown token') ?>
                    <div class="small text-muted"><?= e($scan['reason']) ?> · <?= e($scan['created_at']) ?></div>
                </div>
            <?php endforeach; ?>
            <?php if (!$scans): ?>
                <p class="text-muted mb-0">No scans yet.</p>
            <?php endif; ?>
        </div>
        <div class="panel-card">
            <h2 class="h5">Security notices</h2>
            <?php foreach ($announcements as $item): ?>
                <div class="mb-3">
                    <strong><?= e($item['title']) ?></strong>
                    <div><?= nl2br(e($item['content'])) ?></div>
                </div>
            <?php endforeach; ?>
            <?php if (!$announcements): ?>
                <p class="text-muted mb-0">No security announcements.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require ROOT_PATH . '/includes/layout/footer.php'; ?>
