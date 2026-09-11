<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/init.php';
require_permission('announcements.edit');

$item = find_announcement(get_int('id'));
if (!$item) {
    flash_set('danger', 'Announcement not found.');
    redirect('admin/announcements/index.php');
}

$pageTitle = 'Edit announcement';
$currentNav = 'announcements';
$actor = current_user();

if (is_post()) {
    require_csrf();
    $title = post_string('title');
    $content = post_string('content');
    $audience = post_string('audience');
    $status = post_string('status');

    $errors = [];
    if ($title === '' || mb_strlen($title) > 160) {
        $errors[] = 'Enter a valid title.';
    }
    if ($content === '') {
        $errors[] = 'Enter announcement content.';
    }
    if (!in_array($audience, ['all', 'residents', 'security'], true)) {
        $errors[] = 'Select a valid audience.';
    }
    if (!in_array($status, ['draft', 'published', 'inactive'], true)) {
        $errors[] = 'Select a valid status.';
    }

    if ($errors) {
        flash_set('danger', implode(' ', $errors));
    } else {
        save_announcement([
            'title' => $title,
            'content' => $content,
            'audience' => $audience,
            'status' => $status,
            'published_at' => $item['published_at'] ?: now(),
        ], (int) $item['id']);
        log_activity((int) $actor['id'], 'update_announcement', 'Announcement updated: ' . $title, 'announcements', 'announcement', (int) $item['id']);
        flash_set('success', 'Announcement updated.');
        redirect('admin/announcements/index.php');
    }
}

require ROOT_PATH . '/includes/layout/header.php';
?>
<div class="panel-card">
    <form method="post">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label">Title</label>
            <input class="form-control" name="title" value="<?= e($item['title']) ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Content</label>
            <textarea class="form-control" name="content" rows="6" required><?= e($item['content']) ?></textarea>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Audience</label>
                <select class="form-select" name="audience">
                    <option value="all"<?= selected($item['audience'], 'all') ?>>All</option>
                    <option value="residents"<?= selected($item['audience'], 'residents') ?>>Residents</option>
                    <option value="security"<?= selected($item['audience'], 'security') ?>>Security</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                    <option value="draft"<?= selected($item['status'], 'draft') ?>>Draft</option>
                    <option value="published"<?= selected($item['status'], 'published') ?>>Published</option>
                    <option value="inactive"<?= selected($item['status'], 'inactive') ?>>Inactive</option>
                </select>
            </div>
        </div>
        <div class="mt-4">
            <button class="btn btn-success" type="submit">Save</button>
            <a class="btn btn-outline-secondary" href="<?= e(app_url('admin/announcements/index.php')) ?>">Cancel</a>
        </div>
    </form>
</div>
<?php require ROOT_PATH . '/includes/layout/footer.php'; ?>
