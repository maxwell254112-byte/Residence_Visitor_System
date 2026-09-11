<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/init.php';
require_role('resident');

$resident = require_own_resident();
if ($resident['status'] !== 'active') {
    flash_set('danger', 'Only active residents can register visitors.');
    redirect('resident/visitors/index.php');
}

$pageTitle = 'Register visitor';
$currentNav = 'visitors';
$actor = current_user();

if (is_post()) {
    require_csrf();
    $data = [
        'resident_id' => (int) $resident['id'],
        'visitor_name' => post_string('visitor_name'),
        'car_plate' => post_string('car_plate'),
        'phone' => post_string('phone'),
        'purpose' => post_string('purpose'),
        'visit_date' => post_string('visit_date'),
        'valid_until' => post_string('valid_until'),
        'status' => 'approved',
    ];

    $errors = [];
    if (!validate_name($data['visitor_name'])) {
        $errors[] = 'Enter a valid visitor name.';
    }
    if (!validate_phone($data['phone'])) {
        $errors[] = 'Enter a valid visitor phone.';
    }
    if (!validate_plate($data['car_plate'])) {
        $errors[] = 'Enter a valid car plate.';
    }
    if ($data['purpose'] === '') {
        $errors[] = 'Enter a visit purpose.';
    }
    if ($data['visit_date'] === '' || $data['visit_date'] < today()) {
        $errors[] = 'Visit date cannot be in the past.';
    }
    if ($data['valid_until'] !== '' && $data['valid_until'] < $data['visit_date']) {
        $errors[] = 'Valid-until cannot be before the visit date.';
    }
    if (is_blacklisted($data['phone'], $data['car_plate'])) {
        $errors[] = 'This visitor cannot be registered. Please contact the management office.';
    }

    if ($errors) {
        flash_set('danger', implode(' ', $errors));
    } else {
        $id = create_visitor_record($data, (int) $actor['id'], 'resident');
        flash_set('success', 'Visitor registered. Share the QR from the visitor details page.');
        redirect('resident/visitors/view.php?id=' . $id);
    }
}

require ROOT_PATH . '/includes/layout/header.php';
?>
<div class="panel-card">
    <form method="post">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Visitor name</label>
                <input class="form-control" name="visitor_name" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Phone</label>
                <input class="form-control" name="phone" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Car plate</label>
                <input class="form-control" name="car_plate">
            </div>
            <div class="col-md-6">
                <label class="form-label">Purpose</label>
                <input class="form-control" name="purpose" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Visit date</label>
                <input class="form-control" type="date" name="visit_date" value="<?= e(today()) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Valid until (optional)</label>
                <input class="form-control" type="date" name="valid_until">
            </div>
        </div>
        <div class="mt-4">
            <button class="btn btn-success" type="submit">Register visitor</button>
            <a class="btn btn-outline-secondary" href="<?= e(app_url('resident/visitors/index.php')) ?>">Cancel</a>
        </div>
    </form>
</div>
<?php require ROOT_PATH . '/includes/layout/footer.php'; ?>
