<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/init.php';
require_role('security', 'admin');
redirect('security/dashboard.php');
