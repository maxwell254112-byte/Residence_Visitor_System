<?php
declare(strict_types=1);

/**
 * Application configuration.
 * Adjust values for the target environment. Do not commit production secrets.
 */

define('APP_NAME', 'Residence Visitor System');
define('APP_VERSION', '1.0.0');

/**
 * Leave empty to auto-detect from the current request.
 * Example manual values:
 *   http://localhost/ResidenceManagement
 *   https://example.com
 *   https://example.com/residence
 */
define('APP_URL', '');

define('APP_TIMEZONE', 'Asia/Kuala_Lumpur');

/**
 * Set to false on production hosting.
 * When true, password-reset links may be shown on screen for local testing.
 */
define('APP_DEBUG', false);

/**
 * Show demo accounts on the login page.
 * Set to false before production deployment.
 */
define('SHOW_DEMO_ACCOUNTS', true);

define('SESSION_NAME', 'RVSSESSID');
define('SESSION_IDLE_SECONDS', 1800);
define('SESSION_REGENERATE_SECONDS', 300);

define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_MINUTES', 15);

define('PASSWORD_RESET_MINUTES', 60);
define('PASSWORD_MIN_LENGTH', 10);

define('PAGINATION_PER_PAGE', 15);

define('INVITE_LINK_DEFAULT_DAYS', 7);
define('QR_TOKEN_BYTES', 32);

define('MAIL_FROM', 'noreply@localhost');
define('MAIL_FROM_NAME', APP_NAME);
