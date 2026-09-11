<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/init.php';
require_permission('logs.view');

$pageTitle = 'Activity logs';
$currentNav = 'logs';
$filters = [
    'q' => get_string('q'),
    'module' => get_string('module'),
    'date_from' => get_string('date_from'),
    'date_to' => get_string('date_to'),
];
$result = list_activity_logs($filters, get_int('page', 1));

require ROOT_PATH . '/includes/layout/header.php';
?>
<div class="panel-card mb-3">
    <form class="row g-2 align-items-end" method="get">
        <div class="col-md-4">
            <label class="form-label">Search</label>
            <input class="form-control" name="q" value="<?= e($filters['q']) ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">Module</label>
            <input class="form-control" name="module" value="<?= e($filters['module']) ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">From</label>
            <input class="form-control" type="date" name="date_from" value="<?= e($filters['date_from']) ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">To</label>
            <input class="form-control" type="date" name="date_to" value="<?= e($filters['date_to']) ?>">
        </div>
        <div class="col-md-2">
            <button class="btn btn-success" type="submit">Filter</button>
        </div>
    </form>
</div>
<div class="panel-card">
    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Module</th><th>Details</th></tr></thead>
            <tbody>
            <?php foreach ($result['rows'] as $row): ?>
                <tr>
                    <td><?= e($row['created_at']) ?></td>
                    <td><?= e($row['full_name'] ?: $row['username'] ?: 'System') ?></td>
                    <td><?= e($row['action']) ?></td>
                    <td><?= e($row['module']) ?></td>
                    <td><?= e($row['description']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= pagination_html($result['meta'], app_url('admin/logs/index.php')) ?>
</div>
<?php require ROOT_PATH . '/includes/layout/footer.php'; ?>
