<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

if (is_logged_in()) {
    redirect(home_path());
}

if (is_post()) {
    require_csrf();
    $username = post_string('username');
    $password = (string) ($_POST['password'] ?? '');
    remember_old(['username' => $username]);

    if (attempt_login($username, $password)) {
        clear_old();
        $intended = (string) ($_SESSION['_intended'] ?? '');
        unset($_SESSION['_intended']);
        $user = current_user();
        if ($intended !== '' && !str_contains($intended, 'login.php')) {
            header('Location: ' . $intended);
            exit;
        }
        redirect(home_path($user));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= e(app_url('assets/css/app.css')) ?>">
</head>
<body>
<div class="auth-wrap">
    <div class="auth-card">
        <h1><?= e(APP_NAME) ?></h1>
        <p class="lead-text mb-4">Sign in to manage residents, visitors, and gate access.</p>
        <?php foreach (flash_all() as $flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endforeach; ?>
        <form method="post" autocomplete="off">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label" for="username">Username</label>
                <input class="form-control" id="username" name="username" value="<?= e(old('username')) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Password</label>
                <input class="form-control" id="password" type="password" name="password" required>
            </div>
            <button class="btn btn-success w-100" type="submit">Sign in</button>
        </form>
        <div class="mt-3">
            <a href="<?= e(app_url('forgot-password.php')) ?>">Forgot password?</a>
        </div>
        <?php if (SHOW_DEMO_ACCOUNTS): ?>
            <div class="demo-accounts mt-4">
                <div class="small text-muted mb-2">Demo accounts — click to fill</div>
                <button class="demo-acc" type="button" data-user="admin" data-pass="Admin@12345">
                    <strong>Admin</strong>
                    <span>admin / Admin@12345</span>
                </button>
                <button class="demo-acc" type="button" data-user="security" data-pass="Security@12345">
                    <strong>Security</strong>
                    <span>security / Security@12345</span>
                </button>
                <button class="demo-acc" type="button" data-user="resident" data-pass="Resident@12345">
                    <strong>Resident</strong>
                    <span>resident / Resident@12345</span>
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>
<script>
document.querySelectorAll('.demo-acc').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.getElementById('username').value = btn.getAttribute('data-user') || '';
        document.getElementById('password').value = btn.getAttribute('data-pass') || '';
        document.getElementById('username').focus();
    });
});
</script>
</body>
</html>
