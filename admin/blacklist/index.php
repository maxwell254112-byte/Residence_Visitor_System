<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/init.php';
require_permission('blacklist.view');

$pageTitle = 'Visitor blacklist';
$currentNav = 'blacklist';
$filters = [
    'q' => get_string('q'),
    'active' => get_string('active'),
];
$result = list_blacklist($filters, get_int('page', 1));

require ROOT_PATH . '/includes/layout/header.php';
?>
<div class="panel-card mb-3">
    <form class="row g-2 align-items-end" method="get">
        <div class="col-md-5">
            <label class="form-label">Search</label>
            <input class="form-control" name="q" value="<?= e($filters['q']) ?>" placeholder="Name, plate, phone, reason">
        </div>
        <div class="col-md-3">
            <label class="form-label">Status</label>
            <select class="form-select" name="active">
                <option value="">All</option>
                <option value="1"<?= selected($filters['active'], '1') ?>>Active</option>
                <option value="0"<?= selected($filters['active'], '0') ?>>Inactive</option>
            </select>
        </div>
        <div class="col-md-4">
            <button class="btn btn-success" type="submit">Filter</button>
            <?php if (can('blacklist.manage')): ?>
                <a class="btn btn-outline-success" href="<?= e(app_url('admin/blacklist/create.php')) ?>">Add entry</a>
            <?php endif; ?>
        </div>
    </form>
</div>
<div class="panel-card">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Name</th><th>Plate</th><th>Phone</th><th>Reason</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($result['rows'] as $row): ?>
                <tr>
                    <td><?= e((string) $row['name'] ?: '—') ?></td>
                    <td><?= e((string) $row['car_plate'] ?: '—') ?></td>
                    <td><?= e((string) $row['phone'] ?: '—') ?></td>
                    <td><?= e($row['reason']) ?></td>
                    <td><?= (int) $row['is_active'] === 1 ? status_badge('active') : status_badge('inactive') ?></td>
                    <td>
                        <?php if (can('blacklist.manage')): ?>
                            <a class="btn btn-sm btn-outline-success" href="<?= e(app_url('admin/blacklist/edit.php?id=' . (int) $row['id'])) ?>">Edit</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$result['rows']): ?>
                <tr><td colspan="6" class="text-muted">No blacklist records.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?= pagination_html($result['meta'], app_url('admin/blacklist/index.php')) ?>
</div>
<?php require ROOT_PATH . '/includes/layout/footer.php'; ?>
