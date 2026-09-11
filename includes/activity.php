<?php
declare(strict_types=1);

function log_activity(
    ?int $userId,
    string $action,
    string $description,
    string $module = 'system',
    ?string $entityType = null,
    ?int $entityId = null
): void {
    try {
        $stmt = db()->prepare(
            'INSERT INTO activity_logs
                (user_id, action, description, module, entity_type, entity_id, ip_address, user_agent, created_at)
             VALUES
                (:user_id, :action, :description, :module, :entity_type, :entity_id, :ip, :ua, :created)'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':action' => $action,
            ':description' => $description,
            ':module' => $module,
            ':entity_type' => $entityType,
            ':entity_id' => $entityId,
            ':ip' => client_ip(),
            ':ua' => user_agent(),
            ':created' => now(),
        ]);
    } catch (Throwable $e) {
        app_log('activity_log_failed', $e->getMessage());
    }
}

function list_activity_logs(array $filters, int $page = 1, int $perPage = PAGINATION_PER_PAGE): array
{
    $where = ['1=1'];
    $params = [];

    if (!empty($filters['q'])) {
        $where[] = '(a.action LIKE :q OR a.description LIKE :q2 OR u.username LIKE :q3)';
        $like = '%' . $filters['q'] . '%';
        $params[':q'] = $like;
        $params[':q2'] = $like;
        $params[':q3'] = $like;
    }
    if (!empty($filters['module'])) {
        $where[] = 'a.module = :module';
        $params[':module'] = $filters['module'];
    }
    if (!empty($filters['date_from'])) {
        $where[] = 'a.created_at >= :from';
        $params[':from'] = $filters['date_from'] . ' 00:00:00';
    }
    if (!empty($filters['date_to'])) {
        $where[] = 'a.created_at <= :to';
        $params[':to'] = $filters['date_to'] . ' 23:59:59';
    }

    $sqlWhere = implode(' AND ', $where);
    $count = db()->prepare("SELECT COUNT(*) FROM activity_logs a LEFT JOIN users u ON u.id = a.user_id WHERE {$sqlWhere}");
    $count->execute($params);
    $meta = paginate((int) $count->fetchColumn(), $page, $perPage);

    $stmt = db()->prepare(
        "SELECT a.*, u.username, u.full_name
         FROM activity_logs a
         LEFT JOIN users u ON u.id = a.user_id
         WHERE {$sqlWhere}
         ORDER BY a.id DESC
         LIMIT {$meta['per_page']} OFFSET {$meta['offset']}"
    );
    $stmt->execute($params);

    return ['rows' => $stmt->fetchAll() ?: [], 'meta' => $meta];
}

function recent_activity(int $limit = 8): array
{
    $limit = max(1, min(50, $limit));
    $stmt = db()->query(
        "SELECT a.*, u.username, u.full_name
         FROM activity_logs a
         LEFT JOIN users u ON u.id = a.user_id
         ORDER BY a.id DESC
         LIMIT {$limit}"
    );

    return $stmt->fetchAll() ?: [];
}
