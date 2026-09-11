<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/init.php';
require_permission('residents.view');

$resident = find_resident(get_int('id'));
if (!$resident) {
    flash_set('danger', 'Resident not found.');
    redirect('admin/residents/index.php');
}

$pageTitle = 'Resident history';
$currentNav = 'residents';
$rows = resident_history((int) $resident['id']);

require ROOT_PATH . '/includes/layout/header.php';
?>
<div class="panel-card">
    <h2 class="h5 mb-3"><?= e($resident['full_name']) ?> · <?= e($resident['resident_code']) ?></h2>
    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Details</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['created_at']) ?></td>
                    <td><?= e($row['full_name'] ?: $row['username'] ?: 'System') ?></td>
                    <td><?= e($row['action']) ?></td>
                    <td><?= e($row['description']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
                <tr><td colspan="4" class="text-muted">No history recorded yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <a class="btn btn-outline-secondary" href="<?= e(app_url('admin/residents/view.php?id=' . (int) $resident['id'])) ?>">Back</a>
</div>
<?php require ROOT_PATH . '/includes/layout/footer.php'; ?>
