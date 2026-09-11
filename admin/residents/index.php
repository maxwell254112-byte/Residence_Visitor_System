<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/init.php';
require_permission('residents.view');

$pageTitle = 'Residents';
$currentNav = 'residents';
$filters = [
    'q' => get_string('q'),
    'status' => get_string('status'),
    'gender' => get_string('gender'),
];
$result = list_residents($filters, get_int('page', 1));
$qs = query_string($filters);

require ROOT_PATH . '/includes/layout/header.php';
?>
<div class="panel-card mb-3">
    <form class="row g-2 align-items-end" method="get">
        <div class="col-md-4">
            <label class="form-label">Search</label>
            <input class="form-control" name="q" value="<?= e($filters['q']) ?>" placeholder="Name, code, phone, unit">
        </div>
        <div class="col-md-3">
            <label class="form-label">Status</label>
            <select class="form-select" name="status">
                <option value="">All</option>
                <?php foreach (resident_status_options() as $status): ?>
                    <option value="<?= e($status) ?>"<?= selected($filters['status'], $status) ?>><?= e(ucwords(str_replace('_', ' ', $status))) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Gender</label>
            <select class="form-select" name="gender">
                <option value="">All</option>
                <option value="male"<?= selected($filters['gender'], 'male') ?>>Male</option>
                <option value="female"<?= selected($filters['gender'], 'female') ?>>Female</option>
                <option value="other"<?= selected($filters['gender'], 'other') ?>>Other</option>
            </select>
        </div>
        <div class="col-md-3">
            <button class="btn btn-success" type="submit">Filter</button>
            <?php if (can('residents.create')): ?>
                <a class="btn btn-outline-success" href="<?= e(app_url('admin/residents/create.php')) ?>">Add resident</a>
            <?php endif; ?>
        </div>
    </form>
</div>
<div class="panel-card">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
            <tr>
                <th>Code</th><th>Name</th><th>Unit</th><th>Phone</th><th>Status</th><th>Account</th><th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($result['rows'] as $row): ?>
                <tr>
                    <td><?= e($row['resident_code']) ?></td>
                    <td><?= e($row['full_name']) ?></td>
                    <td><?= e($row['unit_number']) ?></td>
                    <td><?= e($row['phone']) ?></td>
                    <td><?= status_badge($row['status']) ?></td>
                    <td><?= e($row['username'] ?: '—') ?></td>
                    <td class="text-nowrap">
                        <a class="btn btn-sm btn-outline-secondary" href="<?= e(app_url('admin/residents/view.php?id=' . (int) $row['id'])) ?>">View</a>
                        <?php if (can('residents.edit')): ?>
                            <a class="btn btn-sm btn-outline-success" href="<?= e(app_url('admin/residents/edit.php?id=' . (int) $row['id'])) ?>">Edit</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$result['rows']): ?>
                <tr><td colspan="7" class="text-muted">No residents found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?= pagination_html($result['meta'], app_url('admin/residents/index.php' . ($qs !== '' ? '?' . $qs : ''))) ?>
</div>
<?php require ROOT_PATH . '/includes/layout/footer.php'; ?>
