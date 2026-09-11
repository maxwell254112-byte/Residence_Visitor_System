<?php
declare(strict_types=1);

/**
 * CLI smoke check. Not web-accessible when database/.htaccess is active.
 * Usage: php database/_smoke.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

require dirname(__DIR__) . '/includes/init.php';

$required = [
    'roles', 'permissions', 'role_permissions', 'users', 'login_attempts',
    'password_resets', 'residents', 'resident_qr_codes', 'visitors',
    'visitor_invitations', 'visitor_invite_links', 'visitor_scans',
    'visitor_blacklist', 'announcements', 'notifications', 'activity_logs',
];

echo APP_NAME . ' smoke check' . PHP_EOL;
foreach ($required as $table) {
    $stmt = db()->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = :db AND table_name = :table');
    $stmt->execute([':db' => DB_NAME, ':table' => $table]);
    $ok = (int) $stmt->fetchColumn() === 1;
    echo ($ok ? '[OK]  ' : '[MISS] ') . $table . PHP_EOL;
}

$users = (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
$residents = (int) db()->query('SELECT COUNT(*) FROM residents')->fetchColumn();
echo "Users: {$users}" . PHP_EOL;
echo "Residents: {$residents}" . PHP_EOL;
echo 'Timezone: ' . date_default_timezone_get() . ' ' . date('Y-m-d H:i:s') . PHP_EOL;
