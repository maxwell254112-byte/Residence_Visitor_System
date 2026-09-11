<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/init.php';
require_permission('reports.view');

$pageTitle = 'Reports';
$currentNav = 'reports';
$type = get_string('type', 'visitors');
$allowed = ['residents', 'visitors', 'today', 'checkin', 'checkout', 'inside', 'blacklist', 'activity'];
if (!in_array($type, $allowed, true)) {
    $type = 'visitors';
}

$filters = [
    'date_from' => get_string('date_from', date('Y-m-01')),
    'date_to' => get_string('date_to', today()),
];
$dataset = report_dataset($type, $filters);

if (is_post()) {
    require_csrf();
    if (!can('reports.export')) {
        flash_set('danger', 'You do not have permission to export reports.');
        redirect('admin/reports/index.php?type=' . urlencode($type));
    }
    $format = post_string('format');
    $exportType = post_string('type');
    if (!in_array($exportType, $allowed, true)) {
        $exportType = $type;
    }
    $export = report_dataset($exportType, [
        'date_from' => post_string('date_from'),
        'date_to' => post_string('date_to'),
    ]);

    if ($format === 'csv') {
        export_csv(export_filename($exportType, 'csv'), $export['headers'], $export['rows']);
    } elseif ($format === 'xls') {
        export_excel(export_filename($exportType, 'xls'), $export['title'], $export['headers'], $export['rows']);
    } elseif ($format === 'pdf') {
        $pageTitle = $export['title'];
        require ROOT_PATH . '/includes/layout/header.php';
        echo '<div class="panel-card"><h2 class="h4">' . e($export['title']) . '</h2><div class="table-responsive"><table class="table table-sm"><thead><tr>';
        foreach ($export['headers'] as $header) {
            echo '<th>' . e((string) $header) . '</th>';
        }
        echo '</tr></thead><tbody>';
        foreach ($export['rows'] as $row) {
            echo '<tr>';
            foreach ($row as $cell) {
                echo '<td>' . e((string) $cell) . '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody></table></div><button class="btn btn-success mt-3" onclick="window.print()">Print / Save PDF</button></div>';
        require ROOT_PATH . '/includes/layout/footer.php';
        exit;
    }
}

require ROOT_PATH . '/includes/layout/header.php';
?>
<div class="panel-card mb-3">
    <form class="row g-2 align-items-end" method="get">
        <div class="col-md-3">
            <label class="form-label">Report</label>
            <select class="form-select" name="type">
                <option value="residents"<?= selected($type, 'residents') ?>>Residents</option>
                <option value="visitors"<?= selected($type, 'visitors') ?>>Visitors</option>
                <option value="today"<?= selected($type, 'today') ?>>Today's visitors</option>
                <option value="checkin"<?= selected($type, 'checkin') ?>>Check-in</option>
                <option value="checkout"<?= selected($type, 'checkout') ?>>Check-out</option>
                <option value="inside"<?= selected($type, 'inside') ?>>Currently inside</option>
                <option value="blacklist"<?= selected($type, 'blacklist') ?>>Blacklist</option>
                <option value="activity"<?= selected($type, 'activity') ?>>Activity</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">From</label>
            <input class="form-control" type="date" name="date_from" value="<?= e($filters['date_from']) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">To</label>
            <input class="form-control" type="date" name="date_to" value="<?= e($filters['date_to']) ?>">
        </div>
        <div class="col-md-3">
            <button class="btn btn-success" type="submit">View</button>
        </div>
    </form>
    <?php if (can('reports.export')): ?>
        <form class="d-flex flex-wrap gap-2 mt-3" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="type" value="<?= e($type) ?>">
            <input type="hidden" name="date_from" value="<?= e($filters['date_from']) ?>">
            <input type="hidden" name="date_to" value="<?= e($filters['date_to']) ?>">
            <button class="btn btn-outline-success btn-sm" name="format" value="csv">Export CSV</button>
            <button class="btn btn-outline-success btn-sm" name="format" value="xls">Export Excel</button>
            <button class="btn btn-outline-success btn-sm" name="format" value="pdf">Print / PDF</button>
        </form>
    <?php endif; ?>
</div>
<div class="panel-card">
    <h2 class="h5 mb-3"><?= e($dataset['title']) ?></h2>
    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead>
            <tr>
                <?php foreach ($dataset['headers'] as $header): ?>
                    <th><?= e((string) $header) ?></th>
                <?php endforeach; ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($dataset['rows'] as $row): ?>
                <tr>
                    <?php foreach ($row as $cell): ?>
                        <td><?= e((string) $cell) ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            <?php if (!$dataset['rows']): ?>
                <tr><td colspan="<?= max(1, count($dataset['headers'])) ?>" class="text-muted">No records.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require ROOT_PATH . '/includes/layout/footer.php'; ?>
