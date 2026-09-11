<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_login();

$user = current_user();
$pageTitle = 'Change password';
$currentNav = 'password';

if (is_post()) {
    require_csrf();
    $current = (string) ($_POST['current_password'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['password_confirm'] ?? '');
    $errors = password_policy_errors($password);
    if ($password !== $confirm) {
        $errors[] = 'Password confirmation does not match.';
    }
    if ($errors) {
        flash_set('danger', implode(' ', $errors));
    } elseif (change_password((int) $user['id'], $current, $password)) {
        redirect(home_path($user));
    }
}

require ROOT_PATH . '/includes/layout/header.php';
?>
<div class="panel-card" style="max-width: 520px;">
    <form method="post">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label" for="current_password">Current password</label>
            <input class="form-control" id="current_password" type="password" name="current_password" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="password">New password</label>
            <input class="form-control" id="password" type="password" name="password" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="password_confirm">Confirm password</label>
            <input class="form-control" id="password_confirm" type="password" name="password_confirm" required>
        </div>
        <button class="btn btn-success" type="submit">Update password</button>
    </form>
</div>
<?php require ROOT_PATH . '/includes/layout/footer.php'; ?>
