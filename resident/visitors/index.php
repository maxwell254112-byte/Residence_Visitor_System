<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/init.php';
require_role('resident');

$resident = require_own_resident();
$pageTitle = 'My visitors';
$currentNav = 'visitors';
$actor = current_user();

if (is_post()) {
    require_csrf();
    $action = post_string('action');
    if ($action === 'create_link') {
        $days = max(1, min(30, post_int('days', INVITE_LINK_DEFAULT_DAYS)));
        $expires = date('Y-m-d H:i:s', time() + ($days * 86400));
        $link = create_invite_link((int) $resident['id'], $expires, (int) $actor['id']);
        flash_set('success', 'Invitation link created. Share it with your visitor.');
        $_SESSION['_last_invite'] = $link['url'];
    } elseif ($action === 'revoke') {
        if (revoke_invite_link(post_int('id'), (int) $resident['id'], (int) $actor['id'])) {
            flash_set('success', 'Invitation link revoked.');
        } else {
            flash_set('danger', 'The invitation link could not be revoked.');
        }
    }
    redirect('resident/visitors/index.php');
}

$result = list_visitors(['resident_id' => $resident['id'], 'q' => get_string('q'), 'status' => get_string('status')], get_int('page', 1));
$links = list_invite_links((int) $resident['id']);
$lastInvite = (string) ($_SESSION['_last_invite'] ?? '');
unset($_SESSION['_last_invite']);

require ROOT_PATH . '/includes/layout/header.php';
?>
<div class="panel-card mb-3">
    <h2 class="h5">Invitation links</h2>
    <p class="text-muted">Generate a one-time fill-in link. The visitor does not need an account.</p>
    <?php if ($lastInvite !== ''): ?>
        <div class="alert alert-success">
            <div class="mb-2">Share this link:</div>
            <code><?= e($lastInvite) ?></code>
            <button class="btn btn-sm btn-outline-success ms-2" type="button" data-copy="<?= e($lastInvite) ?>" data-label="Copy">Copy</button>
        </div>
    <?php endif; ?>
    <form class="row g-2 align-items-end mb-3" method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create_link">
        <div class="col-auto">
            <label class="form-label">Valid for (days)</label>
            <input class="form-control" type="number" name="days" min="1" max="30" value="<?= INVITE_LINK_DEFAULT_DAYS ?>">
        </div>
        <div class="col-auto">
            <button class="btn btn-success" type="submit">Generate link</button>
        </div>
        <div class="col-auto">
            <a class="btn btn-outline-success" href="<?= e(app_url('resident/visitors/create.php')) ?>">Register visitor myself</a>
        </div>
    </form>
    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead><tr><th>Created</th><th>Expires</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($links as $link): ?>
                <?php
                $state = 'active';
                if ($link['revoked_at']) {
                    $state = 'revoked';
                } elseif ($link['used_at']) {
                    $state = 'used';
                } elseif ((int) $link['is_active'] !== 1 || strtotime((string) $link['expires_at']) < time()) {
                    $state = 'expired';
                }
                ?>
                <tr>
                    <td><?= e($link['created_at']) ?></td>
                    <td><?= e($link['expires_at']) ?></td>
                    <td><?= status_badge($state) ?></td>
                    <td>
                        <?php if ($state === 'active'): ?>
                            <form method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="revoke">
                                <input type="hidden" name="id" value="<?= (int) $link['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger" type="submit">Revoke</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="panel-card">
    <form class="row g-2 align-items-end mb-3" method="get">
        <div class="col-md-4"><input class="form-control" name="q" value="<?= e(get_string('q')) ?>" placeholder="Search my visitors"></div>
        <div class="col-md-3">
            <select class="form-select" name="status">
                <option value="">All statuses</option>
                <?php foreach (visitor_status_options() as $status): ?>
                    <option value="<?= e($status) ?>"<?= selected(get_string('status'), $status) ?>><?= e(ucwords(str_replace('_', ' ', $status))) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-success" type="submit">Filter</button></div>
    </form>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Visitor</th><th>Date</th><th>Plate</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($result['rows'] as $row): ?>
                <tr>
                    <td><?= e($row['visitor_name']) ?></td>
                    <td><?= e($row['visit_date']) ?></td>
                    <td><?= e((string) $row['car_plate'] ?: '—') ?></td>
                    <td><?= status_badge($row['status']) ?></td>
                    <td><a class="btn btn-sm btn-outline-secondary" href="<?= e(app_url('resident/visitors/view.php?id=' . (int) $row['id'])) ?>">View</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= pagination_html($result['meta'], app_url('resident/visitors/index.php')) ?>
</div>
<?php require ROOT_PATH . '/includes/layout/footer.php'; ?>
