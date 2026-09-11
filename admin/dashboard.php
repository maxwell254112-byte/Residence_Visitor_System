<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_permission('dashboard.view');

$pageTitle = 'Admin dashboard';
$currentNav = 'dashboard';
$residents = resident_counts();
$visitors = visitor_counts();
$activities = recent_activity(8);
$announcements = published_announcements('all', 5);

require ROOT_PATH . '/includes/layout/header.php';
?>
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="label">Total residents</div>
            <div class="value"><?= (int) $residents['total'] ?></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="label">Active residents</div>
            <div class="value"><?= (int) $residents['active'] ?></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="label">Today's visitors</div>
            <div class="value"><?= (int) $visitors['today'] ?></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="label">Currently inside</div>
            <div class="value"><?= (int) $visitors['inside'] ?></div>
        </div>
    </div>
</div>
<div class="row g-3">
    <div class="col-lg-7">
        <div class="panel-card">
            <h2 class="h5 mb-3">Recent activity</h2>
            <?php if (!$activities): ?>
                <p class="text-muted mb-0">No activity yet.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Time</th><th>User</th><th>Action</th></tr></thead>
                        <tbody>
                        <?php foreach ($activities as $row): ?>
                            <tr>
                                <td><?= e($row['created_at']) ?></td>
                                <td><?= e($row['full_name'] ?: $row['username'] ?: 'System') ?></td>
                                <td><?= e($row['description']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="panel-card">
            <h2 class="h5 mb-3">Announcements</h2>
            <?php if (!$announcements): ?>
                <p class="text-muted mb-0">No published announcements.</p>
            <?php else: ?>
                <?php foreach ($announcements as $item): ?>
                    <div class="mb-3">
                        <strong><?= e($item['title']) ?></strong>
                        <div class="text-muted"><?= e(mb_strimwidth(strip_tags($item['content']), 0, 140, '…')) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require ROOT_PATH . '/includes/layout/footer.php'; ?>
