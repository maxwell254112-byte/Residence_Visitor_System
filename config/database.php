<?php
declare(strict_types=1);

/**
 * Database credentials.
 * Prefer config/database.local.php for machine-specific secrets (gitignored).
 * XAMPP default: root / empty password.
 * cPanel: create database.local.php or edit the defaults below.
 */

$localDb = __DIR__ . '/database.local.php';
if (is_file($localDb)) {
    require $localDb;
}

if (!defined('DB_HOST')) {
    define('DB_HOST', '127.0.0.1');
}
if (!defined('DB_PORT')) {
    define('DB_PORT', '3306');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', 'residence_management');
}
if (!defined('DB_USER')) {
    define('DB_USER', 'root');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', '');
}
if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', 'utf8mb4');
}
