<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_role('resident');
require_own_resident();

$pageTitle = 'Announcements';
$currentNav = 'announcements';
$items = published_announcements('residents', 30);

require ROOT_PATH . '/includes/layout/header.php';
?>
<div class="panel-card">
    <?php foreach ($items as $item): ?>
        <article class="mb-4">
            <h2 class="h5 mb-1"><?= e($item['title']) ?></h2>
            <div class="small text-muted mb-2"><?= e((string) $item['published_at']) ?></div>
            <div><?= nl2br(e($item['content'])) ?></div>
        </article>
    <?php endforeach; ?>
    <?php if (!$items): ?>
        <p class="text-muted mb-0">No published announcements.</p>
    <?php endif; ?>
</div>
<?php require ROOT_PATH . '/includes/layout/footer.php'; ?>
