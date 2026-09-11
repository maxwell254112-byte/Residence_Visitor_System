<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

define('SETUP_MODE', true);
require dirname(__DIR__) . '/includes/init.php';
require ROOT_PATH . '/includes/setup.php';

try {
    setup_install([
        'host' => DB_HOST,
        'port' => (string) DB_PORT,
        'name' => DB_NAME,
        'user' => DB_USER,
        'pass' => DB_PASS,
    ]);
    echo "Database installed." . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, 'Install failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
