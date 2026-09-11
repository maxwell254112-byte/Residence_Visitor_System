<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_permission('checkin.scan');

$pageTitle = 'Visitor QR scan';
$currentNav = 'scan';
$actor = current_user();
$scanResult = null;
$pendingInvite = null;

if (is_post()) {
    require_csrf();
    $token = post_string('token');
    if (post_string('action') === 'complete_invite') {
        $scanResult = complete_invite_and_checkin($token, [
            'visitor_name' => post_string('visitor_name'),
            'phone' => post_string('phone'),
            'car_plate' => post_string('car_plate'),
            'purpose' => post_string('purpose') ?: 'Visit',
        ], (int) $actor['id']);
    } elseif ($token !== '') {
        $scanResult = process_visitor_checkin($token, (int) $actor['id']);
    }
    if (($scanResult['code'] ?? '') === 'invite_pending') {
        $pendingInvite = $scanResult['invite'] ?? ['token' => $token];
    }
}

require ROOT_PATH . '/includes/layout/header.php';
?>
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<meta name="scan-url" content="<?= e(app_url('api/scan.php')) ?>">
<div class="row g-3">
    <div class="col-lg-7">
        <div class="panel-card">
            <h2 class="h5">Webcam scanner</h2>
            <p class="text-muted">Scan a visitor QR or paste an invitation link. Production hosting must use HTTPS for camera access.</p>
            <div class="scanner-frame mb-3">
                <video id="scanner-video" playsinline muted></video>
                <canvas id="scanner-canvas" class="d-none"></canvas>
            </div>
            <div class="row g-2 align-items-end mb-3">
                <div class="col-md-6">
                    <label class="form-label">Camera</label>
                    <select class="form-select" id="camera-select"></select>
                </div>
                <div class="col-md-6">
                    <button class="btn btn-success" id="start-camera" type="button">Start camera</button>
                    <button class="btn btn-outline-secondary" id="stop-camera" type="button">Stop</button>
                </div>
            </div>
            <div id="scan-status" class="alert alert-secondary<?= $pendingInvite || $scanResult ? ' d-none' : '' ?>">Camera is idle. Start the camera or use manual entry.</div>
            <div id="scan-result" class="<?= $scanResult && !$pendingInvite ? '' : 'd-none' ?> <?= !empty($scanResult['ok']) ? 'alert alert-success' : ($scanResult && !$pendingInvite ? 'alert alert-danger' : '') ?>">
                <?php if ($scanResult && !$pendingInvite): ?>
                    <?= e((string) $scanResult['message']) ?>
                    <?php if (!empty($scanResult['ok']) && !empty($scanResult['visitor'])): ?>
                        <br><?= e((string) $scanResult['visitor']['name']) ?>
                        visiting <?= e((string) $scanResult['visitor']['host']) ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="panel-card mt-3 <?= $pendingInvite ? '' : 'd-none' ?>" id="invite-complete" style="border-color: var(--accent);">
                <h2 class="h5 mb-2">Complete visitor check-in</h2>
                <p class="mb-2">Invitation for <strong id="invite-unit"><?= !empty($pendingInvite['unit']) ? 'unit ' . e((string) $pendingInvite['unit']) : 'this residence' ?></strong>. Fill these fields once, then check in.</p>
                <form id="invite-complete-form" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="complete_invite">
                    <input type="hidden" name="token" value="<?= e((string) ($pendingInvite['token'] ?? '')) ?>">
                    <div class="mb-2">
                        <label class="form-label">Visitor name</label>
                        <input class="form-control" name="visitor_name" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Phone</label>
                        <input class="form-control" name="phone" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Car plate (optional)</label>
                        <input class="form-control" name="car_plate">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Purpose</label>
                        <input class="form-control" name="purpose" value="Visit" required>
                    </div>
                    <button class="btn btn-success w-100" type="submit">Register and check in</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="panel-card">
            <h2 class="h5">Manual token</h2>
            <p class="text-muted">Paste the visitor token or the full invitation link.</p>
            <form id="manual-token-form" method="post">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Token or invitation link</label>
                    <input class="form-control" name="token" autocomplete="off" required>
                </div>
                <button class="btn btn-success" type="submit">Validate & check in</button>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<script src="<?= e(app_url('assets/js/scanner.js?v=3')) ?>"></script>
<?php require ROOT_PATH . '/includes/layout/footer.php'; ?>
