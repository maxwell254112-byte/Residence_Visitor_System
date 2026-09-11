<?php
declare(strict_types=1);

function installed_lock_path(): string
{
    return ROOT_PATH . '/config/installed.lock';
}

function is_setup_script(): bool
{
    return basename((string) ($_SERVER['SCRIPT_NAME'] ?? '')) === 'setup.php';
}

function app_is_installed(): bool
{
    return is_file(installed_lock_path());
}

function mark_app_installed(): void
{
    @file_put_contents(installed_lock_path(), date('c') . PHP_EOL);
}

function db_try(): ?PDO
{
    static $pdo = false;

    if ($pdo !== false) {
        return $pdo instanceof PDO ? $pdo : null;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_CHARSET
    );

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        if (!app_is_installed()) {
            mark_app_installed();
        }
        return $pdo;
    } catch (PDOException $e) {
        app_log('database_connection_failed', $e->getMessage());
        $pdo = null;
        return null;
    }
}

function db(): PDO
{
    $pdo = db_try();
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if (!app_is_installed() && !is_setup_script()) {
        redirect('setup.php');
    }

    http_response_code(500);
    exit('The system is temporarily unavailable. Please try again later.');
}
