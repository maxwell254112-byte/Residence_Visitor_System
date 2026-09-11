<?php
declare(strict_types=1);

function find_visitor(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT v.*, r.full_name AS resident_name, r.unit_number, r.user_id AS resident_user_id,
                inv.id AS invitation_row_id, inv.status AS invitation_status,
                inv.expires_at AS invitation_expires_at, inv.is_active AS invitation_active,
                ci.full_name AS checked_in_by_name, co.full_name AS checked_out_by_name,
                ap.full_name AS approved_by_name
         FROM visitors v
         INNER JOIN residents r ON r.id = v.resident_id
         LEFT JOIN visitor_invitations inv ON inv.id = v.invitation_id
         LEFT JOIN users ci ON ci.id = v.checked_in_by
         LEFT JOIN users co ON co.id = v.checked_out_by
         LEFT JOIN users ap ON ap.id = v.approved_by
         WHERE v.id = :id
         LIMIT 1'
    );
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function normalize_scanned_token(string $raw): string
{
    $raw = trim($raw);
    $raw = trim($raw, "\"'` \t\n\r\0\x0B");
    if ($raw === '') {
        return '';
    }

    if (preg_match('/[?&]token=([A-Fa-f0-9]{32,128})/', $raw, $match)) {
        return strtolower($match[1]);
    }

    if (preg_match('/\b([A-Fa-f0-9]{32,128})\b/', $raw, $match)) {
        return strtolower($match[1]);
    }

    return strtolower($raw);
}

function find_visitor_by_token(string $token): ?array
{
    if (!is_valid_token_format($token)) {
        return null;
    }

    $stmt = db()->prepare(
        'SELECT v.*, r.full_name AS resident_name, r.unit_number, r.user_id AS resident_user_id,
                inv.id AS invitation_row_id, inv.status AS invitation_status,
                inv.expires_at AS invitation_expires_at, inv.is_active AS invitation_active
         FROM visitors v
         INNER JOIN residents r ON r.id = v.resident_id
         LEFT JOIN visitor_invitations inv ON inv.id = v.invitation_id
         WHERE v.qr_token = :token OR inv.token = :token2
         LIMIT 1'
    );
    $stmt->execute([':token' => $token, ':token2' => $token]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function list_visitors(array $filters, int $page = 1): array
{
    $where = ['1=1'];
    $params = [];

    if (!empty($filters['q'])) {
        $where[] = '(v.visitor_name LIKE :q OR v.car_plate LIKE :q2 OR v.phone LIKE :q3 OR r.full_name LIKE :q4 OR r.unit_number LIKE :q5)';
        $like = '%' . $filters['q'] . '%';
        $params[':q'] = $like;
        $params[':q2'] = $like;
        $params[':q3'] = $like;
        $params[':q4'] = $like;
        $params[':q5'] = $like;
    }
    if (!empty($filters['status'])) {
        $where[] = 'v.status = :status';
        $params[':status'] = $filters['status'];
    }
    if (!empty($filters['resident_id'])) {
        $where[] = 'v.resident_id = :resident_id';
        $params[':resident_id'] = (int) $filters['resident_id'];
    }
    if (!empty($filters['date_from'])) {
        $where[] = 'v.visit_date >= :from';
        $params[':from'] = $filters['date_from'];
    }
    if (!empty($filters['date_to'])) {
        $where[] = 'v.visit_date <= :to';
        $params[':to'] = $filters['date_to'];
    }
    if (!empty($filters['inside'])) {
        $where[] = "v.status = 'checked_in'";
    }

    $sqlWhere = implode(' AND ', $where);
    $count = db()->prepare("SELECT COUNT(*) FROM visitors v INNER JOIN residents r ON r.id = v.resident_id WHERE {$sqlWhere}");
    $count->execute($params);
    $meta = paginate((int) $count->fetchColumn(), $page);

    $stmt = db()->prepare(
        "SELECT v.*, r.full_name AS resident_name, r.unit_number
         FROM visitors v
         INNER JOIN residents r ON r.id = v.resident_id
         WHERE {$sqlWhere}
         ORDER BY v.id DESC
         LIMIT {$meta['per_page']} OFFSET {$meta['offset']}"
    );
    $stmt->execute($params);

    return ['rows' => $stmt->fetchAll() ?: [], 'meta' => $meta];
}

function visitor_counts(): array
{
    $today = today();
    $inside = (int) db()->query("SELECT COUNT(*) FROM visitors WHERE status = 'checked_in'")->fetchColumn();
    $todayCount = db()->prepare('SELECT COUNT(*) FROM visitors WHERE visit_date = :d');
    $todayCount->execute([':d' => $today]);
    $pending = (int) db()->query("SELECT COUNT(*) FROM visitors WHERE status IN ('pending','approved')")->fetchColumn();

    return [
        'inside' => $inside,
        'today' => (int) $todayCount->fetchColumn(),
        'pending' => $pending,
    ];
}

function expire_due_visitors(): void
{
    try {
        db()->prepare(
            "UPDATE visitors
             SET status = 'expired', updated_at = :updated
             WHERE status IN ('pending','approved')
               AND valid_until < :now"
        )->execute([':updated' => now(), ':now' => now()]);

        db()->prepare(
            "UPDATE visitor_invite_links
             SET is_active = 0
             WHERE is_active = 1 AND expires_at < :now AND used_at IS NULL"
        )->execute([':now' => now()]);
    } catch (Throwable $e) {
        app_log('expire_visitors_failed', $e->getMessage());
    }
}

function create_visitor_record(array $data, int $actorId, string $source = 'staff'): int
{
    $pdo = db();
    $pdo->beginTransaction();

    try {
        $token = !empty($data['qr_token']) && is_valid_token_format((string) $data['qr_token'])
            ? strtolower(trim((string) $data['qr_token']))
            : secure_token();
        $validUntil = $data['valid_until'] ?: ($data['visit_date'] . ' 23:59:59');
        if (strlen($validUntil) === 10) {
            $validUntil .= ' 23:59:59';
        }

        $status = $data['status'] ?? 'approved';
        $approvedBy = $status === 'approved' ? $actorId : null;
        $approvedAt = $status === 'approved' ? now() : null;

        $inv = $pdo->prepare(
            'INSERT INTO visitor_invitations
                (resident_id, token, expires_at, status, is_active, created_at)
             VALUES
                (:resident_id, :token, :expires_at, :status, 1, :created)'
        );
        $inv->execute([
            ':resident_id' => $data['resident_id'],
            ':token' => $token,
            ':expires_at' => $validUntil,
            ':status' => 'active',
            ':created' => now(),
        ]);
        $invitationId = (int) $pdo->lastInsertId();

        $stmt = $pdo->prepare(
            'INSERT INTO visitors
                (resident_id, invitation_id, invite_link_id, visitor_name, car_plate, phone, purpose,
                 visit_date, valid_until, status, qr_token, approved_by, approved_at, created_at, updated_at)
             VALUES
                (:resident_id, :invitation_id, :invite_link_id, :visitor_name, :car_plate, :phone, :purpose,
                 :visit_date, :valid_until, :status, :qr_token, :approved_by, :approved_at, :created, :updated)'
        );
        $stmt->execute([
            ':resident_id' => $data['resident_id'],
            ':invitation_id' => $invitationId,
            ':invite_link_id' => $data['invite_link_id'] ?? null,
            ':visitor_name' => $data['visitor_name'],
            ':car_plate' => $data['car_plate'] !== '' ? normalize_plate($data['car_plate']) : null,
            ':phone' => normalize_phone($data['phone']),
            ':purpose' => $data['purpose'],
            ':visit_date' => $data['visit_date'],
            ':valid_until' => $validUntil,
            ':status' => $status,
            ':qr_token' => $token,
            ':approved_by' => $approvedBy ?: null,
            ':approved_at' => $approvedAt,
            ':created' => now(),
            ':updated' => now(),
        ]);

        $visitorId = (int) $pdo->lastInsertId();
        $pdo->prepare('UPDATE visitor_invitations SET visitor_id = :vid WHERE id = :id')->execute([
            ':vid' => $visitorId,
            ':id' => $invitationId,
        ]);

        $pdo->commit();
        log_activity($actorId ?: null, 'create_visitor', 'Visitor registered: ' . $data['visitor_name'] . ' (' . $source . ')', 'visitors', 'visitor', $visitorId);

        $resident = find_resident((int) $data['resident_id']);
        if ($resident && !empty($resident['user_id']) && !in_array($source, ['resident', 'gate'], true)) {
            notify_user(
                (int) $resident['user_id'],
                'Visitor registered',
                $data['visitor_name'] . ' was registered for ' . $data['visit_date'] . '.',
                'visitor',
                'visitor',
                $visitorId
            );
        }

        return $visitorId;
    } catch (Throwable $e) {
        $pdo->rollBack();
        app_log('create_visitor_failed', $e->getMessage());
        throw $e;
    }
}

function update_visitor(int $id, array $data, int $actorId): void
{
    $validUntil = $data['valid_until'] ?: ($data['visit_date'] . ' 23:59:59');
    if (strlen($validUntil) === 10) {
        $validUntil .= ' 23:59:59';
    }

    db()->prepare(
        'UPDATE visitors
         SET visitor_name = :visitor_name, car_plate = :car_plate, phone = :phone, purpose = :purpose,
             visit_date = :visit_date, valid_until = :valid_until, updated_at = :updated
         WHERE id = :id AND status IN (\'pending\',\'approved\')'
    )->execute([
        ':visitor_name' => $data['visitor_name'],
        ':car_plate' => $data['car_plate'] !== '' ? normalize_plate($data['car_plate']) : null,
        ':phone' => normalize_phone($data['phone']),
        ':purpose' => $data['purpose'],
        ':visit_date' => $data['visit_date'],
        ':valid_until' => $validUntil,
        ':updated' => now(),
        ':id' => $id,
    ]);

    $visitor = find_visitor($id);
    if ($visitor && !empty($visitor['invitation_id'])) {
        db()->prepare('UPDATE visitor_invitations SET expires_at = :expires WHERE id = :id')->execute([
            ':expires' => $validUntil,
            ':id' => $visitor['invitation_id'],
        ]);
    }

    log_activity($actorId, 'update_visitor', 'Visitor updated: ' . $data['visitor_name'], 'visitors', 'visitor', $id);
}

function reject_visitor(int $id, string $reason, int $actorId): void
{
    db()->prepare(
        'UPDATE visitors SET status = :status, rejection_reason = :reason, updated_at = :updated
         WHERE id = :id AND status IN (\'pending\',\'approved\')'
    )->execute([
        ':status' => 'rejected',
        ':reason' => $reason,
        ':updated' => now(),
        ':id' => $id,
    ]);

    $visitor = find_visitor($id);
    if ($visitor && !empty($visitor['invitation_id'])) {
        db()->prepare('UPDATE visitor_invitations SET status = :s, is_active = 0 WHERE id = :id')->execute([
            ':s' => 'revoked',
            ':id' => $visitor['invitation_id'],
        ]);
    }

    log_activity($actorId, 'reject_visitor', 'Visitor rejected: ' . ($visitor['visitor_name'] ?? ''), 'visitors', 'visitor', $id);
}

function checkout_visitor(int $id, int $staffId): array
{
    $visitor = find_visitor($id);
    if (!$visitor) {
        return ['ok' => false, 'message' => 'Visitor record was not found.'];
    }
    if ($visitor['status'] !== 'checked_in') {
        return ['ok' => false, 'message' => 'This visitor is not currently inside.'];
    }

    db()->prepare(
        'UPDATE visitors
         SET status = :status, checked_out_at = :ts, checked_out_by = :staff, updated_at = :updated
         WHERE id = :id'
    )->execute([
        ':status' => 'checked_out',
        ':ts' => now(),
        ':staff' => $staffId,
        ':updated' => now(),
        ':id' => $id,
    ]);

    log_activity($staffId, 'visitor_checkout', 'Visitor checked out: ' . $visitor['visitor_name'], 'visitors', 'visitor', $id);

    if (!empty($visitor['resident_user_id'])) {
        notify_user(
            (int) $visitor['resident_user_id'],
            'Visitor checked out',
            $visitor['visitor_name'] . ' has checked out.',
            'visitor',
            'visitor',
            $id
        );
    }

    return ['ok' => true, 'message' => $visitor['visitor_name'] . ' has been checked out.'];
}

function is_blacklisted(?string $phone, ?string $plate): ?array
{
    $phone = $phone ? normalize_phone($phone) : '';
    $plate = $plate ? normalize_plate($plate) : '';

    if ($phone === '' && $plate === '') {
        return null;
    }

    $sql = 'SELECT * FROM visitor_blacklist WHERE is_active = 1 AND (';
    $parts = [];
    $params = [];

    if ($phone !== '') {
        $parts[] = 'REPLACE(phone, " ", "") = :phone';
        $params[':phone'] = $phone;
    }
    if ($plate !== '') {
        $parts[] = 'REPLACE(UPPER(car_plate), " ", "") = :plate';
        $params[':plate'] = $plate;
    }

    $sql .= implode(' OR ', $parts) . ') LIMIT 1';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();

    return $row ?: null;
}

function record_visitor_scan(?int $visitorId, string $token, int $staffId, string $result, string $reason): void
{
    db()->prepare(
        'INSERT INTO visitor_scans (visitor_id, token, scanned_by, result, reason, created_at)
         VALUES (:visitor_id, :token, :scanned_by, :result, :reason, :created)'
    )->execute([
        ':visitor_id' => $visitorId,
        ':token' => $token,
        ':scanned_by' => $staffId,
        ':result' => $result,
        ':reason' => $reason,
        ':created' => now(),
    ]);
}

function process_visitor_checkin(string $token, int $staffId): array
{
    $token = normalize_scanned_token($token);

    if ($token === '') {
        return fail_scan(null, $token, $staffId, 'Token is required.');
    }
    if (!is_valid_token_format($token)) {
        return fail_scan(null, $token, $staffId, 'The QR token format is invalid.');
    }

    $visitor = find_visitor_by_token($token);

    if (!$visitor) {
        $invite = find_invite_link($token);
        if ($invite && !empty($invite['visitor_id'])) {
            $visitor = find_visitor((int) $invite['visitor_id']);
        } elseif ($invite) {
            if (!invite_link_is_usable($invite)) {
                return fail_scan(null, $token, $staffId, 'This invitation link is no longer active.');
            }

            return [
                'ok' => false,
                'code' => 'invite_pending',
                'message' => 'Invitation found.',
                'invite' => [
                    'token' => $token,
                    'unit' => $invite['unit_number'],
                    'expires_at' => $invite['expires_at'],
                ],
            ];
        }
    }

    if (!$visitor && find_resident_by_qr_token($token)) {
        return fail_scan(null, $token, $staffId, 'This is a resident QR, not a visitor QR. Ask the visitor to present their visitor invitation QR.');
    }

    if (!$visitor) {
        return fail_scan(null, $token, $staffId, 'No matching visitor invitation was found. Scan the visitor QR, not the resident QR or login page.');
    }

    if (empty($visitor['invitation_row_id'])) {
        return fail_scan((int) $visitor['id'], $token, $staffId, 'This visitor invitation is missing.');
    }
    if ((int) $visitor['invitation_active'] !== 1 || $visitor['invitation_status'] !== 'active') {
        return fail_scan((int) $visitor['id'], $token, $staffId, 'This invitation is no longer active.');
    }
    if (!empty($visitor['invitation_expires_at']) && strtotime((string) $visitor['invitation_expires_at']) < time()) {
        return fail_scan((int) $visitor['id'], $token, $staffId, 'This invitation has expired.');
    }

    $today = today();
    if ($visitor['visit_date'] > $today) {
        return fail_scan((int) $visitor['id'], $token, $staffId, 'This visit is scheduled for a later date.');
    }
    if (strtotime((string) $visitor['valid_until']) < time()) {
        db()->prepare("UPDATE visitors SET status = 'expired', updated_at = :u WHERE id = :id")->execute([
            ':u' => now(),
            ':id' => $visitor['id'],
        ]);
        return fail_scan((int) $visitor['id'], $token, $staffId, 'This visit is no longer valid.');
    }

    $blocked = is_blacklisted($visitor['phone'] ?? null, $visitor['car_plate'] ?? null);
    if ($blocked) {
        log_activity($staffId, 'blacklist_block', 'Check-in blocked for a blacklisted visitor.', 'blacklist', 'visitor', (int) $visitor['id']);
        return fail_scan((int) $visitor['id'], $token, $staffId, 'Entry denied. Please refer this visitor to the supervisor.');
    }

    if ($visitor['status'] === 'checked_in') {
        return fail_scan((int) $visitor['id'], $token, $staffId, 'This visitor is already checked in.');
    }
    if (!in_array($visitor['status'], ['pending', 'approved'], true)) {
        return fail_scan((int) $visitor['id'], $token, $staffId, 'This visitor cannot be checked in from the current status.');
    }

    db()->prepare(
        'UPDATE visitors
         SET status = :status, checked_in_at = :ts, checked_in_by = :staff,
             approved_by = COALESCE(approved_by, :staff2), approved_at = COALESCE(approved_at, :ts2),
             updated_at = :updated
         WHERE id = :id'
    )->execute([
        ':status' => 'checked_in',
        ':ts' => now(),
        ':staff' => $staffId,
        ':staff2' => $staffId,
        ':ts2' => now(),
        ':updated' => now(),
        ':id' => $visitor['id'],
    ]);

    record_visitor_scan((int) $visitor['id'], $token, $staffId, 'success', 'Checked in');
    log_activity($staffId, 'visitor_checkin', 'Visitor checked in: ' . $visitor['visitor_name'], 'visitors', 'visitor', (int) $visitor['id']);

    if (!empty($visitor['resident_user_id'])) {
        notify_user(
            (int) $visitor['resident_user_id'],
            'Visitor checked in',
            $visitor['visitor_name'] . ' has arrived and checked in.',
            'visitor',
            'visitor',
            (int) $visitor['id']
        );
    }

    return [
        'ok' => true,
        'message' => 'Check-in successful.',
        'visitor' => [
            'name' => $visitor['visitor_name'],
            'host' => $visitor['resident_name'],
            'unit' => $visitor['unit_number'],
            'plate' => $visitor['car_plate'],
            'purpose' => $visitor['purpose'],
        ],
    ];
}

function fail_scan(?int $visitorId, string $token, int $staffId, string $message): array
{
    if ($token !== '') {
        record_visitor_scan($visitorId, $token, $staffId, 'rejected', $message);
    }

    return ['ok' => false, 'message' => $message];
}

function create_invite_link(int $residentId, string $expiresAt, int $actorId): array
{
    $token = secure_token();
    db()->prepare(
        'INSERT INTO visitor_invite_links (resident_id, token, expires_at, is_active, created_at)
         VALUES (:resident_id, :token, :expires_at, 1, :created)'
    )->execute([
        ':resident_id' => $residentId,
        ':token' => $token,
        ':expires_at' => $expiresAt,
        ':created' => now(),
    ]);

    $id = (int) db()->lastInsertId();
    log_activity($actorId, 'create_invite_link', 'Visitor invitation link created.', 'visitors', 'invite_link', $id);

    return [
        'id' => $id,
        'token' => $token,
        'url' => app_url('visitor/fill.php?token=' . urlencode($token)),
    ];
}

function find_invite_link(string $token): ?array
{
    if (!is_valid_token_format($token)) {
        return null;
    }

    $stmt = db()->prepare(
        'SELECT l.*, r.full_name AS resident_name, r.unit_number, r.status AS resident_status
         FROM visitor_invite_links l
         INNER JOIN residents r ON r.id = l.resident_id
         WHERE l.token = :token
         LIMIT 1'
    );
    $stmt->execute([':token' => $token]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function invite_link_is_usable(array $invite): bool
{
    if ((int) ($invite['is_active'] ?? 0) !== 1) {
        return false;
    }
    if (!empty($invite['revoked_at']) || !empty($invite['used_at'])) {
        return false;
    }
    if (!empty($invite['expires_at']) && strtotime((string) $invite['expires_at']) < time()) {
        return false;
    }
    if (($invite['resident_status'] ?? '') !== 'active') {
        return false;
    }

    return true;
}

function complete_invite_and_checkin(string $token, array $input, int $staffId): array
{
    $token = normalize_scanned_token($token);
    $invite = $token !== '' ? find_invite_link($token) : null;

    if (!$invite || !invite_link_is_usable($invite)) {
        return fail_scan(null, $token, $staffId, 'This invitation link is no longer active.');
    }

    $data = [
        'resident_id' => (int) $invite['resident_id'],
        'invite_link_id' => (int) $invite['id'],
        'qr_token' => $token,
        'visitor_name' => trim((string) ($input['visitor_name'] ?? '')),
        'car_plate' => trim((string) ($input['car_plate'] ?? '')),
        'phone' => trim((string) ($input['phone'] ?? '')),
        'purpose' => trim((string) ($input['purpose'] ?? '')),
        'visit_date' => today(),
        'valid_until' => (string) $invite['expires_at'],
        'status' => 'approved',
    ];

    $errors = [];
    if (!validate_name($data['visitor_name'])) {
        $errors[] = 'Enter a valid visitor name.';
    }
    if (!validate_phone($data['phone'])) {
        $errors[] = 'Enter a valid visitor phone.';
    }
    if (!validate_plate($data['car_plate'])) {
        $errors[] = 'Enter a valid car plate.';
    }
    if ($data['purpose'] === '' || mb_strlen($data['purpose']) > 150) {
        $errors[] = 'Enter a visit purpose.';
    }
    if (is_blacklisted($data['phone'], $data['car_plate'])) {
        return fail_scan(null, $token, $staffId, 'Entry denied. Please refer this visitor to the supervisor.');
    }
    if ($errors) {
        return [
            'ok' => false,
            'code' => 'invite_pending',
            'message' => implode(' ', $errors),
            'invite' => [
                'token' => $token,
                'unit' => $invite['unit_number'],
                'expires_at' => $invite['expires_at'],
            ],
        ];
    }

    $visitorId = create_visitor_record($data, $staffId, 'gate');
    mark_invite_link_used((int) $invite['id'], $visitorId);

    return process_visitor_checkin($token, $staffId);
}

function revoke_invite_link(int $id, int $residentId, int $actorId): bool
{
    $stmt = db()->prepare(
        'UPDATE visitor_invite_links
         SET is_active = 0, revoked_at = :revoked
         WHERE id = :id AND resident_id = :resident_id AND is_active = 1'
    );
    $stmt->execute([
        ':revoked' => now(),
        ':id' => $id,
        ':resident_id' => $residentId,
    ]);

    if ($stmt->rowCount() < 1) {
        return false;
    }

    log_activity($actorId, 'revoke_invite_link', 'Visitor invitation link revoked.', 'visitors', 'invite_link', $id);
    return true;
}

function list_invite_links(int $residentId): array
{
    $stmt = db()->prepare(
        'SELECT * FROM visitor_invite_links
         WHERE resident_id = :id
         ORDER BY id DESC'
    );
    $stmt->execute([':id' => $residentId]);

    return $stmt->fetchAll() ?: [];
}

function mark_invite_link_used(int $id, int $visitorId): void
{
    db()->prepare(
        'UPDATE visitor_invite_links
         SET used_at = :used, visitor_id = :visitor_id, is_active = 0
         WHERE id = :id'
    )->execute([
        ':used' => now(),
        ':visitor_id' => $visitorId,
        ':id' => $id,
    ]);
}

function visitor_status_options(): array
{
    return ['pending', 'approved', 'checked_in', 'checked_out', 'expired', 'rejected'];
}

function list_blacklist(array $filters, int $page = 1): array
{
    $where = ['1=1'];
    $params = [];

    if (!empty($filters['q'])) {
        $where[] = '(name LIKE :q OR car_plate LIKE :q2 OR phone LIKE :q3 OR reason LIKE :q4)';
        $like = '%' . $filters['q'] . '%';
        $params[':q'] = $like;
        $params[':q2'] = $like;
        $params[':q3'] = $like;
        $params[':q4'] = $like;
    }
    if (isset($filters['active']) && $filters['active'] !== '') {
        $where[] = 'is_active = :active';
        $params[':active'] = (int) $filters['active'];
    }

    $sqlWhere = implode(' AND ', $where);
    $count = db()->prepare("SELECT COUNT(*) FROM visitor_blacklist WHERE {$sqlWhere}");
    $count->execute($params);
    $meta = paginate((int) $count->fetchColumn(), $page);

    $stmt = db()->prepare(
        "SELECT b.*, u.full_name AS created_by_name
         FROM visitor_blacklist b
         LEFT JOIN users u ON u.id = b.created_by
         WHERE {$sqlWhere}
         ORDER BY b.id DESC
         LIMIT {$meta['per_page']} OFFSET {$meta['offset']}"
    );
    $stmt->execute($params);

    return ['rows' => $stmt->fetchAll() ?: [], 'meta' => $meta];
}

function save_blacklist(array $data, ?int $id = null, int $actorId = 0): int
{
    if ($id) {
        db()->prepare(
            'UPDATE visitor_blacklist
             SET name = :name, car_plate = :car_plate, phone = :phone, reason = :reason,
                 is_active = :is_active, updated_at = :updated
             WHERE id = :id'
        )->execute([
            ':name' => $data['name'] !== '' ? $data['name'] : null,
            ':car_plate' => $data['car_plate'] !== '' ? normalize_plate($data['car_plate']) : null,
            ':phone' => $data['phone'] !== '' ? normalize_phone($data['phone']) : null,
            ':reason' => $data['reason'],
            ':is_active' => $data['is_active'],
            ':updated' => now(),
            ':id' => $id,
        ]);
        log_activity($actorId, 'update_blacklist', 'Blacklist entry updated.', 'blacklist', 'blacklist', $id);
        return $id;
    }

    db()->prepare(
        'INSERT INTO visitor_blacklist (name, car_plate, phone, reason, is_active, created_by, created_at, updated_at)
         VALUES (:name, :car_plate, :phone, :reason, :is_active, :created_by, :created, :updated)'
    )->execute([
        ':name' => $data['name'] !== '' ? $data['name'] : null,
        ':car_plate' => $data['car_plate'] !== '' ? normalize_plate($data['car_plate']) : null,
        ':phone' => $data['phone'] !== '' ? normalize_phone($data['phone']) : null,
        ':reason' => $data['reason'],
        ':is_active' => $data['is_active'],
        ':created_by' => $actorId,
        ':created' => now(),
        ':updated' => now(),
    ]);

    $newId = (int) db()->lastInsertId();
    log_activity($actorId, 'create_blacklist', 'Blacklist entry created.', 'blacklist', 'blacklist', $newId);

    return $newId;
}

function recent_scans(int $limit = 10): array
{
    $limit = max(1, min(50, $limit));
    $stmt = db()->query(
        "SELECT s.*, v.visitor_name, u.full_name AS staff_name
         FROM visitor_scans s
         LEFT JOIN visitors v ON v.id = s.visitor_id
         LEFT JOIN users u ON u.id = s.scanned_by
         ORDER BY s.id DESC
         LIMIT {$limit}"
    );

    return $stmt->fetchAll() ?: [];
}
