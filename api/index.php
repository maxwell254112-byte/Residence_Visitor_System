<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';

json_response([
    'ok' => true,
    'name' => APP_NAME,
    'version' => APP_VERSION,
]);
