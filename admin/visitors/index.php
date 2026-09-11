<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/init.php';
require_permission('visitors.view');

$pageTitle = 'Visitors';
$currentNav = 'visitors';
$filters = [
    'q' => get_string('q'),
    'status' => get_string('status'),
    'date_from' => get_string('date_from'),
    'date_to' => get_string('date_to'),
];
$result = list_visitors($filters, get_int('page', 1));
$qs = query_string($filters);

require ROOT_PATH . '/includes/layout/header.php';
?>
<div class="panel-card mb-3">
    <form class="row g-2 align-items-end" method="get">
        <div class="col-md-3">
            <label class="form-label">Search</label>
            <input class="form-control" name="q" value="<?= e($filters['q']) ?>" placeholder="Visitor, plate, host, unit">
        </div>
        <div class="col-md-2">
            <label class="form-label">Status</label>
            <select class="form-select" name="status">
                <option value="">All</option>
                <?php foreach (visitor_status_options() as $status): ?>
                    <option value="<?= e($status) ?>"<?= selected($filters['status'], $status) ?>><?= e(ucwords(str_replace('_', ' ', $status))) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">From</label>
            <input class="form-control" type="date" name="date_from" value="<?= e($filters['date_from']) ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">To</label>
            <input class="form-control" type="date" name="date_to" value="<?= e($filters['date_to']) ?>">
        </div>
        <div class="col-md-3">
            <button class="btn btn-success" type="submit">Filter</button>
            <?php if (can('visitors.create')): ?>
                <a class="btn btn-outline-success" href="<?= e(app_url('admin/visitors/create.php')) ?>">Add visitor</a>
            <?php endif; ?>
        </div>
    </form>
</div>
<div class="panel-card">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
            <tr><th>Visitor</th><th>Host</th><th>Visit date</th><th>Plate</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($result['rows'] as $row): ?>
                <tr>
                    <td><?= e($row['visitor_name']) ?></td>
                    <td><?= e($row['resident_name']) ?> <small class="text-muted"><?= e($row['unit_number']) ?></small></td>
                    <td><?= e($row['visit_date']) ?></td>
                    <td><?= e((string) $row['car_plate'] ?: '—') ?></td>
                    <td><?= status_badge($row['status']) ?></td>
                    <td>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= e(app_url('admin/visitors/view.php?id=' . (int) $row['id'])) ?>">View</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$result['rows']): ?>
                <tr><td colspan="6" class="text-muted">No visitors found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?= pagination_html($result['meta'], app_url('admin/visitors/index.php' . ($qs !== '' ? '?' . $qs : ''))) ?>
</div>
<?php require ROOT_PATH . '/includes/layout/footer.php'; ?>
