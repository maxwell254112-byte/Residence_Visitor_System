<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/init.php';
require_permission('announcements.view');

$pageTitle = 'Announcements';
$currentNav = 'announcements';
$result = list_announcements([
    'q' => get_string('q'),
    'status' => get_string('status'),
], get_int('page', 1));

require ROOT_PATH . '/includes/layout/header.php';
?>
<div class="panel-card mb-3">
    <form class="row g-2 align-items-end" method="get">
        <div class="col-md-5">
            <input class="form-control" name="q" value="<?= e(get_string('q')) ?>" placeholder="Search announcements">
        </div>
        <div class="col-md-3">
            <select class="form-select" name="status">
                <option value="">All statuses</option>
                <option value="draft"<?= selected(get_string('status'), 'draft') ?>>Draft</option>
                <option value="published"<?= selected(get_string('status'), 'published') ?>>Published</option>
                <option value="inactive"<?= selected(get_string('status'), 'inactive') ?>>Inactive</option>
            </select>
        </div>
        <div class="col-md-4">
            <button class="btn btn-success" type="submit">Filter</button>
            <?php if (can('announcements.create')): ?>
                <a class="btn btn-outline-success" href="<?= e(app_url('admin/announcements/create.php')) ?>">New announcement</a>
            <?php endif; ?>
        </div>
    </form>
</div>
<div class="panel-card">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Title</th><th>Audience</th><th>Status</th><th>Updated</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($result['rows'] as $row): ?>
                <tr>
                    <td><?= e($row['title']) ?></td>
                    <td><?= e($row['audience']) ?></td>
                    <td><?= status_badge($row['status']) ?></td>
                    <td><?= e($row['updated_at']) ?></td>
                    <td>
                        <?php if (can('announcements.edit')): ?>
                            <a class="btn btn-sm btn-outline-success" href="<?= e(app_url('admin/announcements/edit.php?id=' . (int) $row['id'])) ?>">Edit</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= pagination_html($result['meta'], app_url('admin/announcements/index.php')) ?>
</div>
<?php require ROOT_PATH . '/includes/layout/footer.php'; ?>
