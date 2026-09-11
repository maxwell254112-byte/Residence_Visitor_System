<?php
declare(strict_types=1);

function generate_resident_qr(int $residentId, int $actorId): string
{
    db()->prepare(
        'UPDATE resident_qr_codes
         SET is_active = 0, revoked_at = :revoked
         WHERE resident_id = :resident_id AND is_active = 1'
    )->execute([':revoked' => now(), ':resident_id' => $residentId]);

    $token = secure_token();
    db()->prepare(
        'INSERT INTO resident_qr_codes (resident_id, token, is_active, created_at)
         VALUES (:resident_id, :token, 1, :created)'
    )->execute([
        ':resident_id' => $residentId,
        ':token' => $token,
        ':created' => now(),
    ]);

    log_activity($actorId, 'qr_generate', 'Resident QR generated.', 'residents', 'resident', $residentId);

    return $token;
}

function revoke_resident_qr(int $residentId, int $actorId): void
{
    db()->prepare(
        'UPDATE resident_qr_codes
         SET is_active = 0, revoked_at = :revoked
         WHERE resident_id = :resident_id AND is_active = 1'
    )->execute([':revoked' => now(), ':resident_id' => $residentId]);

    log_activity($actorId, 'qr_revoke', 'Resident QR revoked.', 'residents', 'resident', $residentId);
}

function active_resident_qr(int $residentId): ?array
{
    $stmt = db()->prepare(
        'SELECT * FROM resident_qr_codes
         WHERE resident_id = :id AND is_active = 1
         ORDER BY id DESC
         LIMIT 1'
    );
    $stmt->execute([':id' => $residentId]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function find_resident_by_qr_token(string $token): ?array
{
    if (!is_valid_token_format($token)) {
        return null;
    }

    $stmt = db()->prepare(
        'SELECT r.*, q.token AS qr_token
         FROM resident_qr_codes q
         INNER JOIN residents r ON r.id = q.resident_id
         WHERE q.token = :token AND q.is_active = 1
         LIMIT 1'
    );
    $stmt->execute([':token' => $token]);
    $row = $stmt->fetch();

    return $row ?: null;
}
