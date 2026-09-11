<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

logout_user(true);
session_boot();
flash_set('success', 'You have signed out.');
redirect('login.php');
