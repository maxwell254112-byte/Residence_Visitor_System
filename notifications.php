<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_login();

$user = current_user();
$pageTitle = 'Notifications';
$currentNav = 'notifications';

if (is_post()) {
    require_csrf();
    $action = post_string('action');
    if ($action === 'read_all') {
        mark_all_notifications_read((int) $user['id']);
        flash_set('success', 'All notifications marked as read.');
    } elseif ($action === 'read') {
        mark_notification_read(post_int('id'), (int) $user['id']);
    }
    redirect('notifications.php');
}

$items = list_notifications((int) $user['id']);
require ROOT_PATH . '/includes/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="mb-0 text-muted">You only see notifications sent to your account.</p>
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="read_all">
        <button class="btn btn-outline-secondary btn-sm" type="submit">Mark all read</button>
    </form>
</div>
<div class="panel-card">
    <?php if (!$items): ?>
        <p class="text-muted mb-0">No notifications yet.</p>
    <?php else: ?>
        <div class="list-group list-group-flush">
            <?php foreach ($items as $item): ?>
                <div class="list-group-item <?= (int) $item['is_read'] === 1 ? '' : 'bg-light' ?>">
                    <div class="d-flex justify-content-between gap-3">
                        <div>
                            <strong><?= e($item['title']) ?></strong>
                            <div><?= e($item['message']) ?></div>
                            <small class="text-muted"><?= e($item['created_at']) ?></small>
                        </div>
                        <?php if ((int) $item['is_read'] === 0): ?>
                            <form method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="read">
                                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                <button class="btn btn-sm btn-outline-success" type="submit">Read</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php require ROOT_PATH . '/includes/layout/footer.php'; ?>
