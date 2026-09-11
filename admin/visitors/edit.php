<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/init.php';
require_permission('visitors.edit');

$visitor = find_visitor(get_int('id'));
if (!$visitor || !in_array($visitor['status'], ['pending', 'approved'], true)) {
    flash_set('danger', 'This visitor cannot be edited.');
    redirect('admin/visitors/index.php');
}

$pageTitle = 'Edit visitor';
$currentNav = 'visitors';
$actor = current_user();

if (is_post()) {
    require_csrf();
    $data = [
        'visitor_name' => post_string('visitor_name'),
        'car_plate' => post_string('car_plate'),
        'phone' => post_string('phone'),
        'purpose' => post_string('purpose'),
        'visit_date' => post_string('visit_date'),
        'valid_until' => post_string('valid_until'),
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
    if ($data['visit_date'] === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['visit_date'])) {
        $errors[] = 'Enter a valid visit date.';
    }
    if ($data['valid_until'] !== '' && $data['valid_until'] < $data['visit_date']) {
        $errors[] = 'Valid-until date cannot be before the visit date.';
    }

    if ($errors) {
        flash_set('danger', implode(' ', $errors));
    } else {
        update_visitor((int) $visitor['id'], $data, (int) $actor['id']);
        flash_set('success', 'Visitor updated.');
        redirect('admin/visitors/view.php?id=' . (int) $visitor['id']);
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
                <input class="form-control" name="visitor_name" value="<?= e($visitor['visitor_name']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Phone</label>
                <input class="form-control" name="phone" value="<?= e($visitor['phone']) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Car plate</label>
                <input class="form-control" name="car_plate" value="<?= e((string) $visitor['car_plate']) ?>">
            </div>
            <div class="col-md-8">
                <label class="form-label">Purpose</label>
                <input class="form-control" name="purpose" value="<?= e($visitor['purpose']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Visit date</label>
                <input class="form-control" type="date" name="visit_date" value="<?= e($visitor['visit_date']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Valid until</label>
                <input class="form-control" type="date" name="valid_until" value="<?= e(substr((string) $visitor['valid_until'], 0, 10)) ?>">
            </div>
        </div>
        <div class="mt-4">
            <button class="btn btn-success" type="submit">Save changes</button>
            <a class="btn btn-outline-secondary" href="<?= e(app_url('admin/visitors/view.php?id=' . (int) $visitor['id'])) ?>">Cancel</a>
        </div>
    </form>
</div>
<?php require ROOT_PATH . '/includes/layout/footer.php'; ?>
