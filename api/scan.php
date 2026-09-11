<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';

if (!is_logged_in()) {
    json_response(['ok' => false, 'message' => 'Authentication is required.'], 401);
}

if (!can('checkin.scan')) {
    json_response(['ok' => false, 'message' => 'You are not allowed to check visitors in.'], 403);
}

if (!is_post()) {
    json_response(['ok' => false, 'message' => 'Method not allowed.'], 405);
}

require_json_csrf();

$payload = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($payload)) {
    $payload = [];
}

$token = trim((string) ($payload['token'] ?? post_string('token')));
$action = trim((string) ($payload['action'] ?? post_string('action')));
$user = current_user();

if ($action === 'complete_invite') {
    $result = complete_invite_and_checkin($token, [
        'visitor_name' => (string) ($payload['visitor_name'] ?? post_string('visitor_name')),
        'phone' => (string) ($payload['phone'] ?? post_string('phone')),
        'car_plate' => (string) ($payload['car_plate'] ?? post_string('car_plate')),
        'purpose' => (string) ($payload['purpose'] ?? post_string('purpose')),
    ], (int) $user['id']);
} else {
    $result = process_visitor_checkin($token, (int) $user['id']);
}

$code = 422;
if (!empty($result['ok'])) {
    $code = 200;
} elseif (($result['code'] ?? '') === 'invite_pending') {
    $code = 409;
}

json_response($result, $code);
