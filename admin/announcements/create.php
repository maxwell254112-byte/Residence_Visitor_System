<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/init.php';
require_permission('announcements.create');

$pageTitle = 'New announcement';
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
        $id = save_announcement([
            'title' => $title,
            'content' => $content,
            'audience' => $audience,
            'status' => $status,
            'created_by' => (int) $actor['id'],
        ]);
        log_activity((int) $actor['id'], 'create_announcement', 'Announcement created: ' . $title, 'announcements', 'announcement', $id);
        flash_set('success', 'Announcement saved.');
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
            <input class="form-control" name="title" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Content</label>
            <textarea class="form-control" name="content" rows="6" required></textarea>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Audience</label>
                <select class="form-select" name="audience">
                    <option value="all">All</option>
                    <option value="residents">Residents</option>
                    <option value="security">Security</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                    <option value="inactive">Inactive</option>
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
