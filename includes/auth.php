<?php
declare(strict_types=1);

function current_user(): ?array
{
    static $user = false;

    if ($user !== false) {
        return $user;
    }

    $id = (int) ($_SESSION['user_id'] ?? 0);
    if ($id < 1) {
        $user = null;
        return null;
    }

    $stmt = db()->prepare(
        'SELECT u.*, r.slug AS role_slug, r.name AS role_name
         FROM users u
         INNER JOIN roles r ON r.id = u.role_id
         WHERE u.id = :id
         LIMIT 1'
    );
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    if (!$row || $row['status'] !== 'active') {
        logout_user(false);
        $user = null;
        return null;
    }

    $user = $row;
    return $user;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function require_login(): void
{
    if (!is_logged_in()) {
        $_SESSION['_intended'] = $_SERVER['REQUEST_URI'] ?? app_url();
        redirect('login.php');
    }

    $user = current_user();
    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $forcePages = ['change-password.php', 'logout.php'];

    if ($user && (int) $user['must_change_password'] === 1 && !in_array($script, $forcePages, true)) {
        flash_set('warning', 'You must change your password before continuing.');
        redirect('change-password.php');
    }
}

function attempt_login(string $username, string $password): bool
{
    $username = trim($username);
    if ($username === '' || $password === '') {
        flash_set('danger', 'Please enter your username and password.');
        return false;
    }

    if (login_is_locked($username)) {
        flash_set('danger', 'Too many failed attempts. Please try again later.');
        record_login_attempt($username, false);
        return false;
    }

    $stmt = db()->prepare(
        'SELECT u.*, r.slug AS role_slug
         FROM users u
         INNER JOIN roles r ON r.id = u.role_id
         WHERE u.username = :username
         LIMIT 1'
    );
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        record_login_attempt($username, false);
        flash_set('danger', 'Invalid username or password.');
        return false;
    }

    if ($user['status'] !== 'active') {
        record_login_attempt($username, false);
        flash_set('danger', 'This account is inactive. Please contact the administrator.');
        return false;
    }

    record_login_attempt($username, true);

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['_created'] = time();
    $_SESSION['_last_activity'] = time();
    $_SESSION['_csrf'] = bin2hex(random_bytes(32));

    db()->prepare('UPDATE users SET last_login_at = :ts WHERE id = :id')->execute([
        ':ts' => now(),
        ':id' => $user['id'],
    ]);

    log_activity((int) $user['id'], 'login', 'User signed in.', 'auth', 'user', (int) $user['id']);

    return true;
}

function logout_user(bool $log = true): void
{
    $userId = (int) ($_SESSION['user_id'] ?? 0);
    if ($log && $userId > 0) {
        log_activity($userId, 'logout', 'User signed out.', 'auth', 'user', $userId);
    }

    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) $params['secure'], (bool) $params['httponly']);
    }
    session_destroy();
}

function login_is_locked(string $username): bool
{
    $since = date('Y-m-d H:i:s', time() - (LOGIN_LOCKOUT_MINUTES * 60));
    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM login_attempts
         WHERE username = :username AND success = 0 AND created_at >= :since'
    );
    $stmt->execute([':username' => $username, ':since' => $since]);

    return (int) $stmt->fetchColumn() >= LOGIN_MAX_ATTEMPTS;
}

function record_login_attempt(string $username, bool $success): void
{
    $stmt = db()->prepare(
        'INSERT INTO login_attempts (username, ip_address, user_agent, success, created_at)
         VALUES (:username, :ip, :ua, :success, :created)'
    );
    $stmt->execute([
        ':username' => $username,
        ':ip' => client_ip(),
        ':ua' => user_agent(),
        ':success' => $success ? 1 : 0,
        ':created' => now(),
    ]);
}

function home_path(?array $user = null): string
{
    $user = $user ?? current_user();
    $role = $user['role_slug'] ?? '';

    return match ($role) {
        'admin' => 'admin/dashboard.php',
        'security' => 'security/dashboard.php',
        'resident' => 'resident/dashboard.php',
        default => 'login.php',
    };
}

function create_password_reset(string $usernameOrEmail): bool
{
    $stmt = db()->prepare(
        'SELECT id, username, email, full_name, status FROM users
         WHERE username = :value OR email = :value2
         LIMIT 1'
    );
    $stmt->execute([':value' => $usernameOrEmail, ':value2' => $usernameOrEmail]);
    $user = $stmt->fetch();

    flash_set('success', 'If an account exists, a password reset link has been sent.');

    if (!$user || $user['status'] !== 'active' || empty($user['email'])) {
        return false;
    }

    $token = secure_token();
    $expires = date('Y-m-d H:i:s', time() + (PASSWORD_RESET_MINUTES * 60));

    db()->prepare(
        'INSERT INTO password_resets (user_id, token, expires_at, created_at)
         VALUES (:user_id, :token, :expires, :created)'
    )->execute([
        ':user_id' => $user['id'],
        ':token' => hash('sha256', $token),
        ':expires' => $expires,
        ':created' => now(),
    ]);

    $link = app_url('reset-password.php?token=' . urlencode($token));
    $subject = APP_NAME . ' password reset';
    $body = "Hello {$user['full_name']},\n\nUse this link to reset your password:\n{$link}\n\nThis link expires in " . PASSWORD_RESET_MINUTES . " minutes.\n";
    $headers = 'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . ">\r\n";

    @mail((string) $user['email'], $subject, $body, $headers);

    if (APP_DEBUG) {
        flash_set('info', 'Debug reset link: ' . $link);
    }

    log_activity((int) $user['id'], 'password_reset_requested', 'Password reset requested.', 'auth', 'user', (int) $user['id']);

    return true;
}

function consume_password_reset(string $token, string $password): bool
{
    if (!is_valid_token_format($token)) {
        flash_set('danger', 'This reset link is invalid or has expired.');
        return false;
    }

    $stmt = db()->prepare(
        'SELECT * FROM password_resets
         WHERE token = :token AND used_at IS NULL
         LIMIT 1'
    );
    $stmt->execute([':token' => hash('sha256', $token)]);
    $row = $stmt->fetch();

    if (!$row || strtotime((string) $row['expires_at']) < time()) {
        flash_set('danger', 'This reset link is invalid or has expired.');
        return false;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    db()->prepare('UPDATE users SET password_hash = :hash, must_change_password = 0 WHERE id = :id')->execute([
        ':hash' => $hash,
        ':id' => $row['user_id'],
    ]);
    db()->prepare('UPDATE password_resets SET used_at = :used WHERE id = :id')->execute([
        ':used' => now(),
        ':id' => $row['id'],
    ]);

    log_activity((int) $row['user_id'], 'password_reset', 'Password was reset.', 'auth', 'user', (int) $row['user_id']);
    flash_set('success', 'Your password has been updated. You may now sign in.');

    return true;
}

function change_password(int $userId, string $current, string $newPassword): bool
{
    $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $userId]);
    $hash = $stmt->fetchColumn();

    if (!$hash || !password_verify($current, (string) $hash)) {
        flash_set('danger', 'Current password is incorrect.');
        return false;
    }

    db()->prepare(
        'UPDATE users SET password_hash = :hash, must_change_password = 0 WHERE id = :id'
    )->execute([
        ':hash' => password_hash($newPassword, PASSWORD_DEFAULT),
        ':id' => $userId,
    ]);

    session_regenerate_id(true);
    log_activity($userId, 'password_change', 'User changed password.', 'auth', 'user', $userId);
    flash_set('success', 'Password updated successfully.');

    return true;
}

function password_policy_errors(string $password): array
{
    $errors = [];
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        $errors[] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must include an uppercase letter.';
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must include a lowercase letter.';
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must include a number.';
    }

    return $errors;
}
