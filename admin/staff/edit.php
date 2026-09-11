<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/init.php';
require_permission('staff.edit');

$user = find_user(get_int('id'));
if (!$user || !in_array($user['role_slug'], ['admin', 'security'], true)) {
    flash_set('danger', 'Staff account not found.');
    redirect('admin/staff/index.php');
}

$pageTitle = 'Edit staff';
$currentNav = 'staff';
$actor = current_user();

if (is_post()) {
    require_csrf();
    $email = post_string('email');
    $fullName = post_string('full_name');
    $phone = post_string('phone');
    $roleSlug = post_string('role');
    $status = post_string('status');
    $password = (string) ($_POST['password'] ?? '');

    $errors = [];
    if (!validate_email($email) || $email === '' || email_exists($email, (int) $user['id'])) {
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
    if (!in_array($status, ['active', 'inactive'], true)) {
        $errors[] = 'Select a valid status.';
    }
    if ($password !== '') {
        $errors = array_merge($errors, password_policy_errors($password));
    }
    if ((int) $user['id'] === (int) $actor['id'] && $status === 'inactive') {
        $errors[] = 'You cannot deactivate your own account.';
    }

    $roleId = role_id_by_slug($roleSlug);
    if ($errors || !$roleId) {
        flash_set('danger', implode(' ', $errors ?: ['Invalid role.']));
    } else {
        db()->prepare(
            'UPDATE users
             SET email = :email, full_name = :full_name, phone = :phone, role_id = :role_id,
                 status = :status, updated_at = :updated
             WHERE id = :id'
        )->execute([
            ':email' => $email,
            ':full_name' => $fullName,
            ':phone' => $phone !== '' ? $phone : null,
            ':role_id' => $roleId,
            ':status' => $status,
            ':updated' => now(),
            ':id' => $user['id'],
        ]);

        if ($password !== '') {
            db()->prepare('UPDATE users SET password_hash = :hash, must_change_password = 1 WHERE id = :id')->execute([
                ':hash' => password_hash($password, PASSWORD_DEFAULT),
                ':id' => $user['id'],
            ]);
        }

        log_activity((int) $actor['id'], 'update_staff', 'Staff account updated: ' . $user['username'], 'staff', 'user', (int) $user['id']);
        flash_set('success', 'Staff account updated.');
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
                <label class="form-label">Username</label>
                <input class="form-control" value="<?= e($user['username']) ?>" disabled>
            </div>
            <div class="col-md-6">
                <label class="form-label">Full name</label>
                <input class="form-control" name="full_name" value="<?= e($user['full_name']) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Email</label>
                <input class="form-control" type="email" name="email" value="<?= e($user['email']) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Phone</label>
                <input class="form-control" name="phone" value="<?= e((string) $user['phone']) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Role</label>
                <select class="form-select" name="role">
                    <option value="admin"<?= selected($user['role_slug'], 'admin') ?>>Admin</option>
                    <option value="security"<?= selected($user['role_slug'], 'security') ?>>Security</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                    <option value="active"<?= selected($user['status'], 'active') ?>>Active</option>
                    <option value="inactive"<?= selected($user['status'], 'inactive') ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Reset password (optional)</label>
                <input class="form-control" type="password" name="password">
            </div>
        </div>
        <div class="mt-4">
            <button class="btn btn-success" type="submit">Save</button>
            <a class="btn btn-outline-secondary" href="<?= e(app_url('admin/staff/index.php')) ?>">Cancel</a>
        </div>
    </form>
</div>
<?php require ROOT_PATH . '/includes/layout/footer.php'; ?>
