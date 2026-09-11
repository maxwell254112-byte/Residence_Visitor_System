<?php
declare(strict_types=1);

function user_permissions(?int $userId = null): array
{
    static $cache = [];
    $user = current_user();
    $userId = $userId ?? (int) ($user['id'] ?? 0);

    if ($userId < 1) {
        return [];
    }

    if (isset($cache[$userId])) {
        return $cache[$userId];
    }

    $stmt = db()->prepare(
        'SELECT p.slug
         FROM permissions p
         INNER JOIN role_permissions rp ON rp.permission_id = p.id
         INNER JOIN users u ON u.role_id = rp.role_id
         WHERE u.id = :id'
    );
    $stmt->execute([':id' => $userId]);
    $cache[$userId] = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

    return $cache[$userId];
}

function can(string $permission, ?array $user = null): bool
{
    $user = $user ?? current_user();
    if (!$user) {
        return false;
    }

    $permissions = user_permissions((int) $user['id']);
    return in_array($permission, $permissions, true);
}

function require_permission(string $permission): void
{
    require_login();

    if (!can($permission)) {
        http_response_code(403);
        $pageTitle = 'Access denied';
        $currentNav = '';
        require ROOT_PATH . '/includes/layout/header.php';
        echo '<div class="alert alert-danger">You do not have permission to access this page.</div>';
        require ROOT_PATH . '/includes/layout/footer.php';
        exit;
    }
}

function require_role(string ...$roles): void
{
    require_login();
    $user = current_user();
    $slug = $user['role_slug'] ?? '';

    if (!in_array($slug, $roles, true)) {
        http_response_code(403);
        $pageTitle = 'Access denied';
        $currentNav = '';
        require ROOT_PATH . '/includes/layout/header.php';
        echo '<div class="alert alert-danger">You do not have permission to access this page.</div>';
        require ROOT_PATH . '/includes/layout/footer.php';
        exit;
    }
}

function role_id_by_slug(string $slug): ?int
{
    $stmt = db()->prepare('SELECT id FROM roles WHERE slug = :slug LIMIT 1');
    $stmt->execute([':slug' => $slug]);
    $id = $stmt->fetchColumn();

    return $id ? (int) $id : null;
}

function all_roles(): array
{
    return db()->query('SELECT * FROM roles ORDER BY id')->fetchAll() ?: [];
}

function all_permissions(): array
{
    return db()->query('SELECT * FROM permissions ORDER BY slug')->fetchAll() ?: [];
}

function find_user(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT u.*, r.slug AS role_slug, r.name AS role_name
         FROM users u
         INNER JOIN roles r ON r.id = u.role_id
         WHERE u.id = :id
         LIMIT 1'
    );
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function list_staff(array $filters, int $page = 1): array
{
    $where = ['r.slug IN (\'admin\', \'security\')'];
    $params = [];

    if (!empty($filters['q'])) {
        $where[] = '(u.username LIKE :q OR u.full_name LIKE :q2 OR u.email LIKE :q3)';
        $like = '%' . $filters['q'] . '%';
        $params[':q'] = $like;
        $params[':q2'] = $like;
        $params[':q3'] = $like;
    }
    if (!empty($filters['status'])) {
        $where[] = 'u.status = :status';
        $params[':status'] = $filters['status'];
    }
    if (!empty($filters['role'])) {
        $where[] = 'r.slug = :role';
        $params[':role'] = $filters['role'];
    }

    $sqlWhere = implode(' AND ', $where);
    $count = db()->prepare(
        "SELECT COUNT(*) FROM users u INNER JOIN roles r ON r.id = u.role_id WHERE {$sqlWhere}"
    );
    $count->execute($params);
    $meta = paginate((int) $count->fetchColumn(), $page);

    $stmt = db()->prepare(
        "SELECT u.id, u.username, u.email, u.full_name, u.phone, u.status, u.last_login_at, u.created_at,
                r.name AS role_name, r.slug AS role_slug
         FROM users u
         INNER JOIN roles r ON r.id = u.role_id
         WHERE {$sqlWhere}
         ORDER BY u.id DESC
         LIMIT {$meta['per_page']} OFFSET {$meta['offset']}"
    );
    $stmt->execute($params);

    return ['rows' => $stmt->fetchAll() ?: [], 'meta' => $meta];
}
