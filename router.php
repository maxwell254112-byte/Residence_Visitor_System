<?php
declare(strict_types=1);

$path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$blocked = ['/config/', '/includes/', '/database/'];

foreach ($blocked as $prefix) {
    if ($path === rtrim($prefix, '/') || str_starts_with($path, $prefix)) {
        http_response_code(403);
        exit('Forbidden');
    }
}

$file = __DIR__ . $path;
if ($path !== '/' && is_file($file)) {
    return false;
}

$dir = __DIR__ . rtrim($path, '/');
if ($path !== '/' && is_dir($dir) && is_file($dir . '/index.php')) {
    require $dir . '/index.php';
    return true;
}

require __DIR__ . '/index.php';
return true;
