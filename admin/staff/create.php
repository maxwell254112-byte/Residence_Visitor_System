<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/init.php';
require_permission('staff.create');

$pageTitle = 'Add staff';
$currentNav = 'staff';
$actor = current_user();

if (is_post()) {
    require_csrf();
    $username = post_string('username');
    $email = post_string('email');
    $fullName = post_string('full_name');
    $phone = post_string('phone');
    $roleSlug = post_string('role');
    $password = (string) ($_POST['password'] ?? '');

    $errors = [];
    if ($username === '' || username_exists($username)) {
        $errors[] = 'Choose a unique username.';
    }
    if (!validate_email($email) || $email === '' || email_exists($email)) {
        $errors[] = 'Choose a unique valid email.';
    }
    if (!validate_name($fullName)) {
        $errors[] = 'Enter a valid full name.';
    }
    if ($phone !== '' && !validate_phone($phone)) {
        $errors[] = 'Enter a valid phone number.';
    }
    if (!in_array($roleSlug, ['admin', 'security'], true)) {
        $errors[] = 'Select a valid staff role.';
    }
    $errors = array_merge($errors, password_policy_errors($password));
    $roleId = role_id_by_slug($roleSlug);

    if ($errors || !$roleId) {
        flash_set('danger', implode(' ', $errors ?: ['Invalid role.']));
    } else {
        db()->prepare(
            'INSERT INTO users (username, email, password_hash, role_id, full_name, phone, status, must_change_password, created_at, updated_at)
             VALUES (:username, :email, :password_hash, :role_id, :full_name, :phone, :status, 1, :created, :updated)'
        )->execute([
            ':username' => $username,
            ':email' => $email,
            ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ':role_id' => $roleId,
            ':full_name' => $fullName,
            ':phone' => $phone !== '' ? $phone : null,
            ':status' => 'active',
            ':created' => now(),
            ':updated' => now(),
        ]);
        $id = (int) db()->lastInsertId();
        log_activity((int) $actor['id'], 'create_staff', 'Staff account created: ' . $username, 'staff', 'user', $id);
        flash_set('success', 'Staff account created.');
        redirect('admin/staff/index.php');
    }
}

require ROOT_PATH . '/includes/layout/header.php';
?>
<div class="panel-card">
    <form method="post">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Full name</label>
                <input class="form-control" name="full_name" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Role</label>
                <select class="form-select" name="role" required>
                    <option value="security">Security</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Username</label>
                <input class="form-control" name="username" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Email</label>
                <input class="form-control" type="email" name="email" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Phone</label>
                <input class="form-control" name="phone">
            </div>
            <div class="col-md-6">
                <label class="form-label">Temporary password</label>
                <input class="form-control" type="password" name="password" required>
            </div>
        </div>
        <div class="mt-4">
            <button class="btn btn-success" type="submit">Create staff</button>
            <a class="btn btn-outline-secondary" href="<?= e(app_url('admin/staff/index.php')) ?>">Cancel</a>
        </div>
    </form>
</div>
<?php require ROOT_PATH . '/includes/layout/footer.php'; ?>
