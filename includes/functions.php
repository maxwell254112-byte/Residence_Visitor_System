<?php
declare(strict_types=1);

function app_boot(): void
{
    date_default_timezone_set(APP_TIMEZONE);

    if (!APP_DEBUG) {
        ini_set('display_errors', '0');
        ini_set('display_startup_errors', '0');
        error_reporting(E_ALL);
    } else {
        ini_set('display_errors', '1');
        error_reporting(E_ALL);
    }

    session_boot();

    if (defined('SETUP_MODE') && SETUP_MODE) {
        return;
    }

    if (db_try()) {
        expire_due_visitors();
        return;
    }

    db();
}

function session_boot(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $secure = is_https();

    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();

    if (empty($_SESSION['_created'])) {
        $_SESSION['_created'] = time();
        $_SESSION['_last_activity'] = time();
    }

    if (!empty($_SESSION['_last_activity']) && (time() - (int) $_SESSION['_last_activity']) > SESSION_IDLE_SECONDS) {
        session_unset();
        session_destroy();
        session_start();
        flash_set('warning', 'Your session expired due to inactivity. Please sign in again.');
        return;
    }

    $_SESSION['_last_activity'] = time();

    if ((time() - (int) $_SESSION['_created']) > SESSION_REGENERATE_SECONDS) {
        session_regenerate_id(true);
        $_SESSION['_created'] = time();
    }

    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
}

function is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }

    return isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443;
}

function app_url(string $path = ''): string
{
    $base = trim(APP_URL);
    if ($base === '') {
        $base = detect_base_url();
    }

    $base = rtrim($base, '/');
    $path = ltrim($path, '/');

    return $path === '' ? $base . '/' : $base . '/' . $path;
}

function detect_base_url(): string
{
    $scheme = is_https() ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    $root = str_replace('\\', '/', ROOT_PATH);
    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']) ?: $_SERVER['DOCUMENT_ROOT']) : '';
    $basePath = '';

    if ($docRoot !== '' && str_starts_with($root, str_replace('\\', '/', $docRoot))) {
        $basePath = trim(substr($root, strlen(rtrim($docRoot, '/'))), '/');
    }

    return $scheme . '://' . $host . ($basePath !== '' ? '/' . $basePath : '');
}

function redirect(string $path): void
{
    $url = preg_match('#^https?://#i', $path) ? $path : app_url($path);
    header('Location: ' . $url);
    exit;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function app_log(string $event, string $message): void
{
    $line = sprintf("[%s] %s %s\n", date('Y-m-d H:i:s'), $event, $message);
    @error_log(trim($line));
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function today(): string
{
    return date('Y-m-d');
}

function request_method(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

function is_post(): bool
{
    return request_method() === 'POST';
}

function input(string $key, mixed $default = ''): mixed
{
    return $_POST[$key] ?? $_GET[$key] ?? $default;
}

function post_string(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

function get_string(string $key, string $default = ''): string
{
    $value = $_GET[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

function post_int(string $key, int $default = 0): int
{
    return filter_var($_POST[$key] ?? $default, FILTER_VALIDATE_INT, ['options' => ['default' => $default]]);
}

function get_int(string $key, int $default = 0): int
{
    return filter_var($_GET[$key] ?? $default, FILTER_VALIDATE_INT, ['options' => ['default' => $default]]);
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verify(?string $token = null): bool
{
    $token = $token ?? (string) ($_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $session = (string) ($_SESSION['_csrf'] ?? '');

    return $session !== '' && hash_equals($session, $token);
}

function require_csrf(): void
{
    if (!csrf_verify()) {
        http_response_code(419);
        exit('Invalid security token. Please refresh the page and try again.');
    }
}

function flash_set(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function flash_all(): array
{
    $items = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return is_array($items) ? $items : [];
}

function old(string $key, string $default = ''): string
{
    $stored = $_SESSION['_old'][$key] ?? $default;
    return is_string($stored) ? $stored : $default;
}

function remember_old(array $data): void
{
    $_SESSION['_old'] = $data;
}

function clear_old(): void
{
    unset($_SESSION['_old']);
}

function client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return filter_var($ip, FILTER_VALIDATE_IP) ?: '0.0.0.0';
}

function user_agent(): string
{
    return substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
}

function secure_token(int $bytes = QR_TOKEN_BYTES): string
{
    return bin2hex(random_bytes($bytes));
}

function is_valid_token_format(string $token): bool
{
    return (bool) preg_match('/^[a-f0-9]{32,128}$/i', $token);
}

function paginate(int $total, int $page, int $perPage = PAGINATION_PER_PAGE): array
{
    $perPage = max(1, $perPage);
    $totalPages = max(1, (int) ceil($total / $perPage));
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $perPage;

    return [
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => $totalPages,
        'offset' => $offset,
    ];
}

function pagination_html(array $meta, string $baseUrl): string
{
    if ($meta['total_pages'] <= 1) {
        return '';
    }

    $html = '<nav aria-label="Pagination"><ul class="pagination pagination-sm mb-0">';
    $sep = str_contains($baseUrl, '?') ? '&' : '?';

    for ($i = 1; $i <= $meta['total_pages']; $i++) {
        $active = $i === $meta['page'] ? ' active' : '';
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . e($baseUrl . $sep . 'page=' . $i) . '">' . $i . '</a></li>';
    }

    $html .= '</ul></nav>';
    return $html;
}

function validate_name(string $name): bool
{
    return (bool) preg_match('/^[\p{L}\p{M}\s\.\'\-]{2,100}$/u', $name);
}

function validate_phone(string $phone): bool
{
    return (bool) preg_match('/^[0-9+\-\s()]{8,20}$/', $phone);
}

function validate_email(string $email): bool
{
    return $email === '' || (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validate_plate(string $plate): bool
{
    return $plate === '' || (bool) preg_match('/^[A-Za-z0-9\-\s]{2,20}$/', $plate);
}

function normalize_phone(string $phone): string
{
    return preg_replace('/\s+/', '', $phone) ?? $phone;
}

function normalize_plate(string $plate): string
{
    return strtoupper(preg_replace('/\s+/', '', $plate) ?? $plate);
}

function selected(string $current, string $expected): string
{
    return $current === $expected ? ' selected' : '';
}

function checked(bool $value): string
{
    return $value ? ' checked' : '';
}

function status_badge(string $status): string
{
    $map = [
        'active' => 'success',
        'approved' => 'success',
        'checked_in' => 'primary',
        'published' => 'success',
        'pending' => 'warning',
        'suspended' => 'danger',
        'rejected' => 'danger',
        'expired' => 'secondary',
        'inactive' => 'secondary',
        'moved_out' => 'dark',
        'checked_out' => 'secondary',
        'draft' => 'secondary',
        'revoked' => 'danger',
        'used' => 'info',
    ];

    $class = $map[$status] ?? 'secondary';
    $label = ucwords(str_replace('_', ' ', $status));

    return '<span class="badge text-bg-' . $class . '">' . e($label) . '</span>';
}

function json_response(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function require_json_csrf(): void
{
    $token = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_csrf'] ?? '');
    if (!csrf_verify($token)) {
        json_response(['ok' => false, 'message' => 'Invalid security token.'], 419);
    }
}

function query_string(array $overrides = []): string
{
    $params = array_merge($_GET, $overrides);
    unset($params['page']);
    return http_build_query(array_filter($params, static fn ($v) => $v !== '' && $v !== null));
}
