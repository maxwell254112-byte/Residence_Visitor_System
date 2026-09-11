<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

if (is_logged_in()) {
    redirect(home_path());
}

if (is_post()) {
    require_csrf();
    create_password_reset(post_string('account'));
    redirect('forgot-password.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot password · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= e(app_url('assets/css/app.css')) ?>">
</head>
<body>
<div class="auth-wrap">
    <div class="auth-card">
        <h1>Reset password</h1>
        <p class="lead-text mb-4">Enter your username or email. If an account exists, a reset link will be sent.</p>
        <?php foreach (flash_all() as $flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endforeach; ?>
        <form method="post">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label" for="account">Username or email</label>
                <input class="form-control" id="account" name="account" required>
            </div>
            <button class="btn btn-success w-100" type="submit">Send reset link</button>
        </form>
        <div class="mt-3"><a href="<?= e(app_url('login.php')) ?>">Back to sign in</a></div>
    </div>
</div>
</body>
</html>
