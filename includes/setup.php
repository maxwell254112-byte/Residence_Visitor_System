<?php
declare(strict_types=1);

function setup_write_local_config(array $config): void
{
    $lines = [
        '<?php',
        'declare(strict_types=1);',
        '',
        'define(\'DB_HOST\', ' . var_export($config['host'], true) . ');',
        'define(\'DB_PORT\', ' . var_export($config['port'], true) . ');',
        'define(\'DB_NAME\', ' . var_export($config['name'], true) . ');',
        'define(\'DB_USER\', ' . var_export($config['user'], true) . ');',
        'define(\'DB_PASS\', ' . var_export($config['pass'], true) . ');',
        'define(\'DB_CHARSET\', \'utf8mb4\');',
        '',
    ];

    $path = ROOT_PATH . '/config/database.local.php';
    if (file_put_contents($path, implode(PHP_EOL, $lines), LOCK_EX) === false) {
        throw new RuntimeException('Could not write the local database configuration file.');
    }
}

function setup_split_sql(string $sql): array
{
    $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
    $statements = [];
    $buffer = '';
    $inString = false;
    $quote = '';
    $length = strlen($sql);

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $buffer .= $char;

        if ($inString) {
            if ($char === '\\') {
                $buffer .= $sql[++$i] ?? '';
                continue;
            }
            if ($char === $quote) {
                $inString = false;
            }
            continue;
        }

        if ($char === '\'' || $char === '"') {
            $inString = true;
            $quote = $char;
            continue;
        }

        if ($char === ';') {
            $statement = trim(substr($buffer, 0, -1));
            if ($statement !== '') {
                $statements[] = $statement;
            }
            $buffer = '';
        }
    }

    $tail = trim($buffer);
    if ($tail !== '') {
        $statements[] = $tail;
    }

    return $statements;
}

function setup_import_schema(PDO $pdo): void
{
    $file = ROOT_PATH . '/database/database.sql';
    if (!is_file($file)) {
        throw new RuntimeException('The database schema file is missing.');
    }

    $sql = (string) file_get_contents($file);
    foreach (setup_split_sql($sql) as $statement) {
        $pdo->exec($statement);
    }
}

function setup_install(array $config): void
{
    if (!preg_match('/^[A-Za-z0-9._-]+$/', $config['host'])) {
        throw new InvalidArgumentException('Enter a valid database host.');
    }
    if (!preg_match('/^[0-9]+$/', $config['port']) || (int) $config['port'] < 1 || (int) $config['port'] > 65535) {
        throw new InvalidArgumentException('Enter a valid database port.');
    }
    if (!preg_match('/^[A-Za-z0-9_]+$/', $config['name'])) {
        throw new InvalidArgumentException('Enter a valid database name.');
    }
    if ($config['user'] === '' || strlen($config['user']) > 80) {
        throw new InvalidArgumentException('Enter a valid database username.');
    }

    $dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $config['host'], $config['port']);
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . $config['name'] . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $pdo->exec('USE `' . $config['name'] . '`');
    setup_import_schema($pdo);
    setup_write_local_config($config);
    mark_app_installed();
}
