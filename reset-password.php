<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$token = get_string('token');
if ($token === '') {
    flash_set('danger', 'This reset link is invalid or has expired.');
    redirect('forgot-password.php');
}

if (is_post()) {
    require_csrf();
    $token = post_string('token');
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['password_confirm'] ?? '');
    $errors = password_policy_errors($password);
    if ($password !== $confirm) {
        $errors[] = 'Password confirmation does not match.';
    }
    if ($errors) {
        flash_set('danger', implode(' ', $errors));
    } elseif (consume_password_reset($token, $password)) {
        redirect('login.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Set a new password · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= e(app_url('assets/css/app.css')) ?>">
</head>
<body>
<div class="auth-wrap">
    <div class="auth-card">
        <h1>Set a new password</h1>
        <p class="lead-text mb-4">Choose a strong password with at least <?= PASSWORD_MIN_LENGTH ?> characters, including upper, lower, and a number.</p>
        <?php foreach (flash_all() as $flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endforeach; ?>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= e($token) ?>">
            <div class="mb-3">
                <label class="form-label" for="password">New password</label>
                <input class="form-control" id="password" type="password" name="password" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="password_confirm">Confirm password</label>
                <input class="form-control" id="password_confirm" type="password" name="password_confirm" required>
            </div>
            <button class="btn btn-success w-100" type="submit">Update password</button>
        </form>
    </div>
</div>
</body>
</html>
