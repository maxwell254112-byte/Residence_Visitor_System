<?php
declare(strict_types=1);

function generate_resident_code(): string
{
    $year = date('Y');
    $stmt = db()->prepare(
        'SELECT resident_code FROM residents
         WHERE resident_code LIKE :prefix
         ORDER BY id DESC
         LIMIT 1'
    );
    $stmt->execute([':prefix' => 'RES-' . $year . '-%']);
    $last = (string) $stmt->fetchColumn();
    $seq = 1;

    if ($last !== '' && preg_match('/RES-\d{4}-(\d+)/', $last, $m)) {
        $seq = (int) $m[1] + 1;
    }

    return sprintf('RES-%s-%04d', $year, $seq);
}

function find_resident(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT r.*, u.username, u.email AS user_email, u.status AS user_status
         FROM residents r
         LEFT JOIN users u ON u.id = r.user_id
         WHERE r.id = :id
         LIMIT 1'
    );
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function resident_for_user(int $userId): ?array
{
    $stmt = db()->prepare('SELECT * FROM residents WHERE user_id = :id LIMIT 1');
    $stmt->execute([':id' => $userId]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function require_own_resident(): array
{
    require_login();
    $user = current_user();

    if (($user['role_slug'] ?? '') === 'admin') {
        $id = get_int('resident_id') ?: get_int('id');
        $resident = $id ? find_resident($id) : null;
        if ($resident) {
            return $resident;
        }
    }

    $resident = resident_for_user((int) $user['id']);
    if (!$resident) {
        http_response_code(403);
        $pageTitle = 'No resident profile';
        $currentNav = '';
        require ROOT_PATH . '/includes/layout/header.php';
        echo '<div class="alert alert-warning">Your account is not linked to a resident profile.</div>';
        require ROOT_PATH . '/includes/layout/footer.php';
        exit;
    }

    return $resident;
}

function list_residents(array $filters, int $page = 1): array
{
    $where = ['1=1'];
    $params = [];

    if (!empty($filters['q'])) {
        $where[] = '(r.full_name LIKE :q OR r.resident_code LIKE :q2 OR r.phone LIKE :q3 OR r.unit_number LIKE :q4 OR r.email LIKE :q5)';
        $like = '%' . $filters['q'] . '%';
        $params[':q'] = $like;
        $params[':q2'] = $like;
        $params[':q3'] = $like;
        $params[':q4'] = $like;
        $params[':q5'] = $like;
    }
    if (!empty($filters['status'])) {
        $where[] = 'r.status = :status';
        $params[':status'] = $filters['status'];
    }
    if (!empty($filters['gender'])) {
        $where[] = 'r.gender = :gender';
        $params[':gender'] = $filters['gender'];
    }

    $sqlWhere = implode(' AND ', $where);
    $count = db()->prepare("SELECT COUNT(*) FROM residents r WHERE {$sqlWhere}");
    $count->execute($params);
    $meta = paginate((int) $count->fetchColumn(), $page);

    $stmt = db()->prepare(
        "SELECT r.*, u.username
         FROM residents r
         LEFT JOIN users u ON u.id = r.user_id
         WHERE {$sqlWhere}
         ORDER BY r.id DESC
         LIMIT {$meta['per_page']} OFFSET {$meta['offset']}"
    );
    $stmt->execute($params);

    return ['rows' => $stmt->fetchAll() ?: [], 'meta' => $meta];
}

function resident_counts(): array
{
    $rows = db()->query('SELECT status, COUNT(*) AS c FROM residents GROUP BY status')->fetchAll() ?: [];
    $out = ['total' => 0, 'active' => 0, 'pending' => 0, 'suspended' => 0, 'moved_out' => 0, 'inactive' => 0];
    foreach ($rows as $row) {
        $out[$row['status']] = (int) $row['c'];
        $out['total'] += (int) $row['c'];
    }

    return $out;
}

function save_resident(array $data, ?int $id = null, ?int $actorId = null): int
{
    $fields = [
        'full_name' => $data['full_name'],
        'gender' => $data['gender'],
        'phone' => $data['phone'],
        'email' => $data['email'] !== '' ? $data['email'] : null,
        'address' => $data['address'] !== '' ? $data['address'] : null,
        'unit_number' => $data['unit_number'],
        'emergency_contact' => $data['emergency_contact'] !== '' ? $data['emergency_contact'] : null,
        'emergency_contact_phone' => $data['emergency_contact_phone'] !== '' ? $data['emergency_contact_phone'] : null,
        'status' => $data['status'],
        'updated_at' => now(),
    ];

    if ($id) {
        $sql = 'UPDATE residents SET
                    full_name = :full_name, gender = :gender, phone = :phone, email = :email,
                    address = :address, unit_number = :unit_number, emergency_contact = :emergency_contact,
                    emergency_contact_phone = :emergency_contact_phone, status = :status, updated_at = :updated_at
                WHERE id = :id';
        $fields['id'] = $id;
        db()->prepare($sql)->execute(array_combine(array_map(fn ($k) => ':' . $k, array_keys($fields)), $fields));
        log_activity($actorId, 'update_resident', 'Resident updated: ' . $data['full_name'], 'residents', 'resident', $id);
        return $id;
    }

    $code = generate_resident_code();
    $stmt = db()->prepare(
        'INSERT INTO residents
            (user_id, resident_code, full_name, gender, phone, email, address, unit_number,
             emergency_contact, emergency_contact_phone, status, created_at, updated_at)
         VALUES
            (:user_id, :resident_code, :full_name, :gender, :phone, :email, :address, :unit_number,
             :emergency_contact, :emergency_contact_phone, :status, :created_at, :updated_at)'
    );
    $stmt->execute([
        ':user_id' => $data['user_id'] ?? null,
        ':resident_code' => $code,
        ':full_name' => $data['full_name'],
        ':gender' => $data['gender'],
        ':phone' => $data['phone'],
        ':email' => $fields['email'],
        ':address' => $fields['address'],
        ':unit_number' => $data['unit_number'],
        ':emergency_contact' => $fields['emergency_contact'],
        ':emergency_contact_phone' => $fields['emergency_contact_phone'],
        ':status' => $data['status'],
        ':created_at' => now(),
        ':updated_at' => now(),
    ]);

    $newId = (int) db()->lastInsertId();
    generate_resident_qr($newId, $actorId ?? 0);
    log_activity($actorId, 'create_resident', 'Resident created: ' . $data['full_name'], 'residents', 'resident', $newId);

    return $newId;
}

function link_resident_account(int $residentId, array $account, int $actorId): void
{
    $roleId = role_id_by_slug('resident');
    if (!$roleId) {
        throw new RuntimeException('Resident role is missing.');
    }

    $stmt = db()->prepare(
        'INSERT INTO users (username, email, password_hash, role_id, full_name, phone, status, must_change_password, created_at, updated_at)
         VALUES (:username, :email, :password_hash, :role_id, :full_name, :phone, :status, 1, :created, :updated)'
    );
    $stmt->execute([
        ':username' => $account['username'],
        ':email' => $account['email'],
        ':password_hash' => password_hash($account['password'], PASSWORD_DEFAULT),
        ':role_id' => $roleId,
        ':full_name' => $account['full_name'],
        ':phone' => $account['phone'] !== '' ? $account['phone'] : null,
        ':status' => 'active',
        ':created' => now(),
        ':updated' => now(),
    ]);

    $userId = (int) db()->lastInsertId();
    db()->prepare('UPDATE residents SET user_id = :user_id WHERE id = :id')->execute([
        ':user_id' => $userId,
        ':id' => $residentId,
    ]);

    log_activity($actorId, 'link_resident_account', 'Resident account created.', 'residents', 'resident', $residentId);
}

function username_exists(string $username, ?int $exceptId = null): bool
{
    $sql = 'SELECT COUNT(*) FROM users WHERE username = :username';
    $params = [':username' => $username];
    if ($exceptId) {
        $sql .= ' AND id <> :id';
        $params[':id'] = $exceptId;
    }
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn() > 0;
}

function email_exists(string $email, ?int $exceptId = null): bool
{
    if ($email === '') {
        return false;
    }
    $sql = 'SELECT COUNT(*) FROM users WHERE email = :email';
    $params = [':email' => $email];
    if ($exceptId) {
        $sql .= ' AND id <> :id';
        $params[':id'] = $exceptId;
    }
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn() > 0;
}

function resident_history(int $residentId, int $limit = 50): array
{
    $limit = max(1, min(100, $limit));
    $stmt = db()->prepare(
        "SELECT a.*, u.full_name, u.username
         FROM activity_logs a
         LEFT JOIN users u ON u.id = a.user_id
         WHERE (a.entity_type = 'resident' AND a.entity_id = :id)
            OR (a.module = 'visitors' AND a.entity_id IN (SELECT id FROM visitors WHERE resident_id = :id2))
         ORDER BY a.id DESC
         LIMIT {$limit}"
    );
    $stmt->execute([':id' => $residentId, ':id2' => $residentId]);

    return $stmt->fetchAll() ?: [];
}

function resident_status_options(): array
{
    return ['pending', 'active', 'suspended', 'moved_out', 'inactive'];
}
