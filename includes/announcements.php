<?php
declare(strict_types=1);

function list_announcements(array $filters, int $page = 1): array
{
    $where = ['1=1'];
    $params = [];

    if (!empty($filters['status'])) {
        $where[] = 'a.status = :status';
        $params[':status'] = $filters['status'];
    }
    if (!empty($filters['audience'])) {
        $where[] = '(a.audience = :audience OR a.audience = :all)';
        $params[':audience'] = $filters['audience'];
        $params[':all'] = 'all';
    }
    if (!empty($filters['q'])) {
        $where[] = '(a.title LIKE :q OR a.content LIKE :q2)';
        $params[':q'] = '%' . $filters['q'] . '%';
        $params[':q2'] = '%' . $filters['q'] . '%';
    }

    $sqlWhere = implode(' AND ', $where);
    $count = db()->prepare("SELECT COUNT(*) FROM announcements a WHERE {$sqlWhere}");
    $count->execute($params);
    $meta = paginate((int) $count->fetchColumn(), $page);

    $stmt = db()->prepare(
        "SELECT a.*, u.full_name AS author_name
         FROM announcements a
         LEFT JOIN users u ON u.id = a.created_by
         WHERE {$sqlWhere}
         ORDER BY a.id DESC
         LIMIT {$meta['per_page']} OFFSET {$meta['offset']}"
    );
    $stmt->execute($params);

    return ['rows' => $stmt->fetchAll() ?: [], 'meta' => $meta];
}

function published_announcements(string $audience, int $limit = 10): array
{
    $stmt = db()->prepare(
        'SELECT * FROM announcements
         WHERE status = :status
           AND (audience = :audience OR audience = :all)
         ORDER BY published_at DESC, id DESC
         LIMIT ' . max(1, min(30, $limit))
    );
    $stmt->execute([
        ':status' => 'published',
        ':audience' => $audience,
        ':all' => 'all',
    ]);

    return $stmt->fetchAll() ?: [];
}

function find_announcement(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM announcements WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function save_announcement(array $data, ?int $id = null): int
{
    $publishedAt = $data['status'] === 'published' ? ($data['published_at'] ?? now()) : null;

    if ($id) {
        $stmt = db()->prepare(
            'UPDATE announcements
             SET title = :title, content = :content, audience = :audience,
                 status = :status, published_at = :published_at, updated_at = :updated
             WHERE id = :id'
        );
        $stmt->execute([
            ':title' => $data['title'],
            ':content' => $data['content'],
            ':audience' => $data['audience'],
            ':status' => $data['status'],
            ':published_at' => $publishedAt,
            ':updated' => now(),
            ':id' => $id,
        ]);
        return $id;
    }

    $stmt = db()->prepare(
        'INSERT INTO announcements (title, content, audience, status, created_by, published_at, created_at, updated_at)
         VALUES (:title, :content, :audience, :status, :created_by, :published_at, :created, :updated)'
    );
    $stmt->execute([
        ':title' => $data['title'],
        ':content' => $data['content'],
        ':audience' => $data['audience'],
        ':status' => $data['status'],
        ':created_by' => $data['created_by'],
        ':published_at' => $publishedAt,
        ':created' => now(),
        ':updated' => now(),
    ]);

    return (int) db()->lastInsertId();
}
