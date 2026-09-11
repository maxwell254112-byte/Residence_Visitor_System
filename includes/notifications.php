<?php
declare(strict_types=1);

function notify_user(
    int $userId,
    string $title,
    string $message,
    string $type = 'system',
    ?string $relatedType = null,
    ?int $relatedId = null
): void {
    $stmt = db()->prepare(
        'INSERT INTO notifications (user_id, title, message, type, related_type, related_id, is_read, created_at)
         VALUES (:user_id, :title, :message, :type, :related_type, :related_id, 0, :created)'
    );
    $stmt->execute([
        ':user_id' => $userId,
        ':title' => $title,
        ':message' => $message,
        ':type' => $type,
        ':related_type' => $relatedType,
        ':related_id' => $relatedId,
        ':created' => now(),
    ]);
}

function list_notifications(int $userId, bool $unreadOnly = false, int $limit = 50): array
{
    $sql = 'SELECT * FROM notifications WHERE user_id = :user_id';
    if ($unreadOnly) {
        $sql .= ' AND is_read = 0';
    }
    $sql .= ' ORDER BY id DESC LIMIT ' . max(1, min(100, $limit));

    $stmt = db()->prepare($sql);
    $stmt->execute([':user_id' => $userId]);

    return $stmt->fetchAll() ?: [];
}

function unread_notification_count(int $userId): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :id AND is_read = 0');
    $stmt->execute([':id' => $userId]);

    return (int) $stmt->fetchColumn();
}

function mark_notification_read(int $id, int $userId): void
{
    $stmt = db()->prepare('UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :user_id');
    $stmt->execute([':id' => $id, ':user_id' => $userId]);
}

function mark_all_notifications_read(int $userId): void
{
    $stmt = db()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = :user_id');
    $stmt->execute([':user_id' => $userId]);
}
