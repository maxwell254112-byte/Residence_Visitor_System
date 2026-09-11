<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';

$token = get_string('token') ?: post_string('token');
$link = $token !== '' ? find_invite_link($token) : null;
$error = '';
$created = null;

if (!$link) {
    $error = 'This invitation link is invalid.';
} elseif ((int) $link['is_active'] !== 1 || $link['revoked_at'] || $link['used_at']) {
    $error = 'This invitation link is no longer active.';
} elseif (strtotime((string) $link['expires_at']) < time()) {
    $error = 'This invitation link has expired.';
} elseif ($link['resident_status'] !== 'active') {
    $error = 'This invitation is not available.';
}

if ($error === '' && is_post()) {
    require_csrf();
    $data = [
        'resident_id' => (int) $link['resident_id'],
        'invite_link_id' => (int) $link['id'],
        'visitor_name' => post_string('visitor_name'),
        'car_plate' => post_string('car_plate'),
        'phone' => post_string('phone'),
        'purpose' => post_string('purpose'),
        'visit_date' => post_string('visit_date'),
        'valid_until' => post_string('valid_until'),
        'status' => 'approved',
        'qr_token' => $token,
    ];

    $errors = [];
    if (!validate_name($data['visitor_name'])) {
        $errors[] = 'Enter a valid name.';
    }
    if (!validate_phone($data['phone'])) {
        $errors[] = 'Enter a valid phone number.';
    }
    if (!validate_plate($data['car_plate'])) {
        $errors[] = 'Enter a valid car plate.';
    }
    if ($data['purpose'] === '') {
        $errors[] = 'Enter the purpose of your visit.';
    }
    if ($data['visit_date'] === '' || $data['visit_date'] < today()) {
        $errors[] = 'Visit date cannot be in the past.';
    }
    if ($data['valid_until'] !== '' && $data['valid_until'] < $data['visit_date']) {
        $errors[] = 'Valid-until cannot be before the visit date.';
    }
    if (is_blacklisted($data['phone'], $data['car_plate'])) {
        $errors[] = 'Your details cannot be accepted. Please contact the host.';
    }

    if ($errors) {
        $error = implode(' ', $errors);
    } else {
        $visitorId = create_visitor_record($data, 0, 'invite');
        mark_invite_link_used((int) $link['id'], $visitorId);
        $created = find_visitor($visitorId);
        if (!empty($link['resident_id'])) {
            $resident = find_resident((int) $link['resident_id']);
            if ($resident && !empty($resident['user_id'])) {
                notify_user(
                    (int) $resident['user_id'],
                    'Visitor registration created',
                    $data['visitor_name'] . ' submitted a visitor form for ' . $data['visit_date'] . '.',
                    'visitor',
                    'visitor',
                    $visitorId
                );
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Visitor registration · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= e(app_url('assets/css/app.css')) ?>">
</head>
<body class="visitor-wrap">
<div class="visitor-card">
    <h1 class="h4 mb-2">Visitor registration</h1>
    <?php if ($created): ?>
        <p class="text-muted">Your visit has been registered. Show this QR at the guard house.</p>
        <div class="alert alert-success">
            <?= e($created['visitor_name']) ?> · <?= e($created['visit_date']) ?>
        </div>
        <div class="qr-box mb-3" id="visitor-qr" data-token="<?= e($created['qr_token']) ?>"></div>
        <p class="small text-muted mb-0">Keep a screenshot of this page. The QR contains only a random token.</p>
    <?php elseif ($error !== '' && !is_post()): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
    <?php else: ?>
        <p class="text-muted">Please provide your visit details. Only the host unit is shown.</p>
        <div class="mb-3"><strong>Visiting unit</strong><div><?= e((string) ($link['unit_number'] ?? '')) ?></div></div>
        <?php if ($error !== ''): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= e($token) ?>">
            <div class="mb-3">
                <label class="form-label">Full name</label>
                <input class="form-control" name="visitor_name" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Phone</label>
                <input class="form-control" name="phone" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Car plate (optional)</label>
                <input class="form-control" name="car_plate">
            </div>
            <div class="mb-3">
                <label class="form-label">Purpose</label>
                <input class="form-control" name="purpose" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Visit date</label>
                <input class="form-control" type="date" name="visit_date" value="<?= e(today()) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Valid until (optional)</label>
                <input class="form-control" type="date" name="valid_until">
            </div>
            <button class="btn btn-success w-100" type="submit">Submit</button>
        </form>
    <?php endif; ?>
</div>
<?php if ($created): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
(function () {
    var box = document.getElementById('visitor-qr');
    if (box && typeof QRCode !== 'undefined') {
        new QRCode(box, { text: box.getAttribute('data-token'), width: 200, height: 200 });
    }
})();
</script>
<?php endif; ?>
</body>
</html>
