<?php
declare(strict_types=1);

define('SETUP_MODE', true);
require_once __DIR__ . '/includes/init.php';
require_once ROOT_PATH . '/includes/setup.php';

if (app_is_installed() && db_try()) {
    flash_set('info', 'The system is already installed.');
    redirect('login.php');
}

$error = '';
$values = [
    'host' => old('host', DB_HOST),
    'port' => old('port', (string) DB_PORT),
    'name' => old('name', DB_NAME),
    'user' => old('user', DB_USER),
];

if (is_post()) {
    require_csrf();
    $values = [
        'host' => post_string('host'),
        'port' => post_string('port'),
        'name' => post_string('name'),
        'user' => post_string('user'),
        'pass' => (string) ($_POST['pass'] ?? ''),
    ];
    remember_old($values);

    try {
        setup_install($values);
        clear_old();
        flash_set('success', 'Database installed. You can sign in now.');
        redirect('login.php');
    } catch (PDOException $e) {
        app_log('setup_failed', $e->getMessage());
        $error = 'Could not connect or import the database. Check the host, username, and password.';
    } catch (Throwable $e) {
        app_log('setup_failed', $e->getMessage());
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Setup · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= e(app_url('assets/css/app.css')) ?>">
</head>
<body>
<div class="auth-wrap">
    <div class="auth-card">
        <h1>First-time setup</h1>
        <p class="lead-text mb-4">Enter your MySQL / MariaDB account. The installer will create the database and import the schema.</p>
        <?php if ($error !== ''): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>
        <?php foreach (flash_all() as $flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endforeach; ?>
        <form method="post" autocomplete="off">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label" for="host">Host</label>
                <input class="form-control" id="host" name="host" value="<?= e($values['host']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="port">Port</label>
                <input class="form-control" id="port" name="port" value="<?= e($values['port']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="name">Database name</label>
                <input class="form-control" id="name" name="name" value="<?= e($values['name']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="user">Username</label>
                <input class="form-control" id="user" name="user" value="<?= e($values['user']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="pass">Password</label>
                <input class="form-control" id="pass" type="password" name="pass">
            </div>
            <button class="btn btn-success w-100" type="submit">Create database and continue</button>
        </form>
    </div>
</div>
</body>
</html>
