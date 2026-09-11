<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/init.php';
require_permission('visitors.create');

$pageTitle = 'Register visitor';
$currentNav = 'visitors';
$actor = current_user();
$residents = list_residents(['status' => 'active'], 1);
$residentChoices = db()->query("SELECT id, full_name, unit_number FROM residents WHERE status = 'active' ORDER BY full_name")->fetchAll() ?: [];

if (is_post()) {
    require_csrf();
    $data = [
        'resident_id' => post_int('resident_id'),
        'visitor_name' => post_string('visitor_name'),
        'car_plate' => post_string('car_plate'),
        'phone' => post_string('phone'),
        'purpose' => post_string('purpose'),
        'visit_date' => post_string('visit_date'),
        'valid_until' => post_string('valid_until'),
        'status' => 'approved',
    ];
    remember_old($data);

    $errors = [];
    $host = find_resident($data['resident_id']);
    if (!$host || $host['status'] !== 'active') {
        $errors[] = 'Select an active host resident.';
    }
    if (!validate_name($data['visitor_name'])) {
        $errors[] = 'Enter a valid visitor name.';
    }
    if (!validate_phone($data['phone'])) {
        $errors[] = 'Enter a valid visitor phone.';
    }
    if (!validate_plate($data['car_plate'])) {
        $errors[] = 'Enter a valid car plate.';
    }
    if ($data['purpose'] === '' || mb_strlen($data['purpose']) > 150) {
        $errors[] = 'Enter a visit purpose.';
    }
    if ($data['visit_date'] === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['visit_date'])) {
        $errors[] = 'Enter a valid visit date.';
    } elseif ($data['visit_date'] < today()) {
        $errors[] = 'Visit date cannot be in the past.';
    }
    if ($data['valid_until'] !== '' && $data['valid_until'] < $data['visit_date']) {
        $errors[] = 'Valid-until date cannot be before the visit date.';
    }
    if (is_blacklisted($data['phone'], $data['car_plate'])) {
        $errors[] = 'This visitor matches an active blacklist record and cannot be registered.';
    }

    if ($errors) {
        flash_set('danger', implode(' ', $errors));
    } else {
        $id = create_visitor_record($data, (int) $actor['id'], 'admin');
        clear_old();
        flash_set('success', 'Visitor registered.');
        redirect('admin/visitors/view.php?id=' . $id);
    }
}

require ROOT_PATH . '/includes/layout/header.php';
?>
<div class="panel-card">
    <form method="post">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Host resident</label>
                <select class="form-select" name="resident_id" required>
                    <option value="">Select resident</option>
                    <?php foreach ($residentChoices as $row): ?>
                        <option value="<?= (int) $row['id'] ?>"<?= selected((string) old('resident_id'), (string) $row['id']) ?>>
                            <?= e($row['full_name'] . ' · ' . $row['unit_number']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Visitor name</label>
                <input class="form-control" name="visitor_name" value="<?= e(old('visitor_name')) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Phone</label>
                <input class="form-control" name="phone" value="<?= e(old('phone')) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Car plate</label>
                <input class="form-control" name="car_plate" value="<?= e(old('car_plate')) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Purpose</label>
                <input class="form-control" name="purpose" value="<?= e(old('purpose')) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Visit date</label>
                <input class="form-control" type="date" name="visit_date" value="<?= e(old('visit_date', today())) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Valid until (optional)</label>
                <input class="form-control" type="date" name="valid_until" value="<?= e(old('valid_until')) ?>">
            </div>
        </div>
        <div class="mt-4">
            <button class="btn btn-success" type="submit">Register visitor</button>
            <a class="btn btn-outline-secondary" href="<?= e(app_url('admin/visitors/index.php')) ?>">Cancel</a>
        </div>
    </form>
</div>
<?php require ROOT_PATH . '/includes/layout/footer.php'; ?>
