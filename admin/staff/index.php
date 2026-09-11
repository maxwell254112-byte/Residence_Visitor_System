<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/init.php';
require_permission('staff.view');

$pageTitle = 'Staff accounts';
$currentNav = 'staff';
$filters = [
    'q' => get_string('q'),
    'status' => get_string('status'),
    'role' => get_string('role'),
];
$result = list_staff($filters, get_int('page', 1));

require ROOT_PATH . '/includes/layout/header.php';
?>
<div class="panel-card mb-3">
    <form class="row g-2 align-items-end" method="get">
        <div class="col-md-4">
            <label class="form-label">Search</label>
            <input class="form-control" name="q" value="<?= e($filters['q']) ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">Role</label>
            <select class="form-select" name="role">
                <option value="">All</option>
                <option value="admin"<?= selected($filters['role'], 'admin') ?>>Admin</option>
                <option value="security"<?= selected($filters['role'], 'security') ?>>Security</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Status</label>
            <select class="form-select" name="status">
                <option value="">All</option>
                <option value="active"<?= selected($filters['status'], 'active') ?>>Active</option>
                <option value="inactive"<?= selected($filters['status'], 'inactive') ?>>Inactive</option>
            </select>
        </div>
        <div class="col-md-4">
            <button class="btn btn-success" type="submit">Filter</button>
            <?php if (can('staff.create')): ?>
                <a class="btn btn-outline-success" href="<?= e(app_url('admin/staff/create.php')) ?>">Add staff</a>
            <?php endif; ?>
        </div>
    </form>
</div>
<div class="panel-card">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Name</th><th>Username</th><th>Role</th><th>Status</th><th>Last login</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($result['rows'] as $row): ?>
                <tr>
                    <td><?= e($row['full_name']) ?></td>
                    <td><?= e($row['username']) ?></td>
                    <td><?= e($row['role_name']) ?></td>
                    <td><?= status_badge($row['status']) ?></td>
                    <td><?= e((string) $row['last_login_at'] ?: '—') ?></td>
                    <td>
                        <?php if (can('staff.edit')): ?>
                            <a class="btn btn-sm btn-outline-success" href="<?= e(app_url('admin/staff/edit.php?id=' . (int) $row['id'])) ?>">Edit</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= pagination_html($result['meta'], app_url('admin/staff/index.php')) ?>
</div>
<?php require ROOT_PATH . '/includes/layout/footer.php'; ?>
