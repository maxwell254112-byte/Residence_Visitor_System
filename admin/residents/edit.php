<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/init.php';
require_permission('residents.edit');

$resident = find_resident(get_int('id'));
if (!$resident) {
    flash_set('danger', 'Resident not found.');
    redirect('admin/residents/index.php');
}

$pageTitle = 'Edit resident';
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
        'status' => post_string('status'),
    ];

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

    if ($errors) {
        flash_set('danger', implode(' ', $errors));
    } else {
        save_resident($data, (int) $resident['id'], (int) $actor['id']);
        flash_set('success', 'Resident updated.');
        redirect('admin/residents/view.php?id=' . (int) $resident['id']);
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
                <input class="form-control" name="full_name" value="<?= e($resident['full_name']) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Gender</label>
                <select class="form-select" name="gender" required>
                    <option value="male"<?= selected($resident['gender'], 'male') ?>>Male</option>
                    <option value="female"<?= selected($resident['gender'], 'female') ?>>Female</option>
                    <option value="other"<?= selected($resident['gender'], 'other') ?>>Other</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                    <?php foreach (resident_status_options() as $status): ?>
                        <option value="<?= e($status) ?>"<?= selected($resident['status'], $status) ?>><?= e(ucwords(str_replace('_', ' ', $status))) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Phone</label>
                <input class="form-control" name="phone" value="<?= e($resident['phone']) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Email</label>
                <input class="form-control" name="email" value="<?= e((string) $resident['email']) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Unit / house number</label>
                <input class="form-control" name="unit_number" value="<?= e($resident['unit_number']) ?>" required>
            </div>
            <div class="col-12">
                <label class="form-label">Address</label>
                <input class="form-control" name="address" value="<?= e((string) $resident['address']) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Emergency contact</label>
                <input class="form-control" name="emergency_contact" value="<?= e((string) $resident['emergency_contact']) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Emergency contact phone</label>
                <input class="form-control" name="emergency_contact_phone" value="<?= e((string) $resident['emergency_contact_phone']) ?>">
            </div>
        </div>
        <div class="mt-4">
            <button class="btn btn-success" type="submit">Save changes</button>
            <a class="btn btn-outline-secondary" href="<?= e(app_url('admin/residents/view.php?id=' . (int) $resident['id'])) ?>">Cancel</a>
        </div>
    </form>
</div>
<?php require ROOT_PATH . '/includes/layout/footer.php'; ?>
