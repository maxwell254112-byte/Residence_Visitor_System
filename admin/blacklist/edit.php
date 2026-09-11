<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/init.php';
require_permission('blacklist.manage');

$id = get_int('id');
$stmt = db()->prepare('SELECT * FROM visitor_blacklist WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$entry = $stmt->fetch();
if (!$entry) {
    flash_set('danger', 'Blacklist entry not found.');
    redirect('admin/blacklist/index.php');
}

$pageTitle = 'Edit blacklist entry';
$currentNav = 'blacklist';
$actor = current_user();

if (is_post()) {
    require_csrf();
    $data = [
        'name' => post_string('name'),
        'car_plate' => post_string('car_plate'),
        'phone' => post_string('phone'),
        'reason' => post_string('reason'),
        'is_active' => post_string('is_active') === '1' ? 1 : 0,
    ];

    $errors = [];
    if ($data['car_plate'] === '' && $data['phone'] === '') {
        $errors[] = 'Provide a car plate or phone number.';
    }
    if ($data['phone'] !== '' && !validate_phone($data['phone'])) {
        $errors[] = 'Enter a valid phone number.';
    }
    if ($data['car_plate'] !== '' && !validate_plate($data['car_plate'])) {
        $errors[] = 'Enter a valid car plate.';
    }
    if ($data['reason'] === '') {
        $errors[] = 'A reason is required.';
    }

    if ($errors) {
        flash_set('danger', implode(' ', $errors));
    } else {
        save_blacklist($data, $id, (int) $actor['id']);
        flash_set('success', 'Blacklist entry updated.');
        redirect('admin/blacklist/index.php');
    }
}

require ROOT_PATH . '/includes/layout/header.php';
?>
<div class="panel-card">
    <form method="post">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Name</label>
                <input class="form-control" name="name" value="<?= e((string) $entry['name']) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Car plate</label>
                <input class="form-control" name="car_plate" value="<?= e((string) $entry['car_plate']) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Phone</label>
                <input class="form-control" name="phone" value="<?= e((string) $entry['phone']) ?>">
            </div>
            <div class="col-12">
                <label class="form-label">Reason</label>
                <textarea class="form-control" name="reason" rows="3" required><?= e($entry['reason']) ?></textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select class="form-select" name="is_active">
                    <option value="1"<?= selected((string) $entry['is_active'], '1') ?>>Active</option>
                    <option value="0"<?= selected((string) $entry['is_active'], '0') ?>>Inactive</option>
                </select>
            </div>
        </div>
        <div class="mt-4">
            <button class="btn btn-success" type="submit">Save</button>
            <a class="btn btn-outline-secondary" href="<?= e(app_url('admin/blacklist/index.php')) ?>">Cancel</a>
        </div>
    </form>
</div>
<?php require ROOT_PATH . '/includes/layout/footer.php'; ?>
