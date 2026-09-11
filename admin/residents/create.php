<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/init.php';
require_permission('residents.create');

$pageTitle = 'Add resident';
$currentNav = 'residents';
$actor = current_user();

if (is_post()) {
    require_csrf();
    $data = [
        'full_name' => post_string('full_name'),
        'gender' => post_string('gender'),
        'phone' => post_string('phone'),
        'email' => post_string('email'),
        'address' => post_string('address'),
        'unit_number' => post_string('unit_number'),
        'emergency_contact' => post_string('emergency_contact'),
        'emergency_contact_phone' => post_string('emergency_contact_phone'),
        'status' => post_string('status') ?: 'pending',
    ];
    remember_old($data);

    $errors = [];
    if (!validate_name($data['full_name'])) {
        $errors[] = 'Enter a valid full name.';
    }
    if (!in_array($data['gender'], ['male', 'female', 'other'], true)) {
        $errors[] = 'Select a valid gender.';
    }
    if (!validate_phone($data['phone'])) {
        $errors[] = 'Enter a valid phone number.';
    }
    if (!validate_email($data['email'])) {
        $errors[] = 'Enter a valid email address.';
    }
    if ($data['unit_number'] === '') {
        $errors[] = 'Unit / house number is required.';
    }
    if (!in_array($data['status'], resident_status_options(), true)) {
        $errors[] = 'Select a valid status.';
    }
    if ($data['emergency_contact_phone'] !== '' && !validate_phone($data['emergency_contact_phone'])) {
        $errors[] = 'Enter a valid emergency contact phone.';
    }

    $createAccount = post_string('create_account') === '1';
    $username = post_string('username');
    $accountEmail = post_string('account_email');
    $password = (string) ($_POST['account_password'] ?? '');

    if ($createAccount) {
        if ($username === '' || username_exists($username)) {
            $errors[] = 'Choose a unique username for the resident account.';
        }
        if (!validate_email($accountEmail) || $accountEmail === '' || email_exists($accountEmail)) {
            $errors[] = 'Choose a unique valid email for the resident account.';
        }
        $errors = array_merge($errors, password_policy_errors($password));
    }

    if ($errors) {
        flash_set('danger', implode(' ', $errors));
    } else {
        $id = save_resident($data, null, (int) $actor['id']);
        if ($createAccount) {
            link_resident_account($id, [
                'username' => $username,
                'email' => $accountEmail,
                'password' => $password,
                'full_name' => $data['full_name'],
                'phone' => $data['phone'],
            ], (int) $actor['id']);
        }
        clear_old();
        flash_set('success', 'Resident created.');
        redirect('admin/residents/view.php?id=' . $id);
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
                <input class="form-control" name="full_name" value="<?= e(old('full_name')) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Gender</label>
                <select class="form-select" name="gender" required>
                    <option value="">Select</option>
                    <option value="male"<?= selected(old('gender'), 'male') ?>>Male</option>
                    <option value="female"<?= selected(old('gender'), 'female') ?>>Female</option>
                    <option value="other"<?= selected(old('gender'), 'other') ?>>Other</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                    <?php foreach (resident_status_options() as $status): ?>
                        <option value="<?= e($status) ?>"<?= selected(old('status', 'pending'), $status) ?>><?= e(ucwords(str_replace('_', ' ', $status))) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Phone</label>
                <input class="form-control" name="phone" value="<?= e(old('phone')) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Email</label>
                <input class="form-control" name="email" value="<?= e(old('email')) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Unit / house number</label>
                <input class="form-control" name="unit_number" value="<?= e(old('unit_number')) ?>" required>
            </div>
            <div class="col-12">
                <label class="form-label">Address</label>
                <input class="form-control" name="address" value="<?= e(old('address')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Emergency contact</label>
                <input class="form-control" name="emergency_contact" value="<?= e(old('emergency_contact')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Emergency contact phone</label>
                <input class="form-control" name="emergency_contact_phone" value="<?= e(old('emergency_contact_phone')) ?>">
            </div>
        </div>
        <hr>
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="create_account" value="1" id="create_account">
            <label class="form-check-label" for="create_account">Create linked resident login</label>
        </div>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Username</label>
                <input class="form-control" name="username">
            </div>
            <div class="col-md-4">
                <label class="form-label">Account email</label>
                <input class="form-control" name="account_email">
            </div>
            <div class="col-md-4">
                <label class="form-label">Temporary password</label>
                <input class="form-control" type="password" name="account_password">
            </div>
        </div>
        <div class="mt-4">
            <button class="btn btn-success" type="submit">Save resident</button>
            <a class="btn btn-outline-secondary" href="<?= e(app_url('admin/residents/index.php')) ?>">Cancel</a>
        </div>
    </form>
</div>
<?php require ROOT_PATH . '/includes/layout/footer.php'; ?>
