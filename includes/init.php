<?php
declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));

if (!defined('SETUP_MODE')) {
    define('SETUP_MODE', false);
}

require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/db.php';
require_once ROOT_PATH . '/includes/auth.php';
require_once ROOT_PATH . '/includes/rbac.php';
require_once ROOT_PATH . '/includes/activity.php';
require_once ROOT_PATH . '/includes/notifications.php';
require_once ROOT_PATH . '/includes/announcements.php';
require_once ROOT_PATH . '/includes/qr.php';
require_once ROOT_PATH . '/includes/residents.php';
require_once ROOT_PATH . '/includes/visitors.php';
require_once ROOT_PATH . '/includes/export.php';

app_boot();
