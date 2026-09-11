<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

if (is_logged_in()) {
    redirect(home_path());
}

redirect('login.php');
