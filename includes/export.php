<?php
declare(strict_types=1);

function export_filename(string $prefix, string $ext): string
{
    return $prefix . '-' . date('Ymd-His') . '.' . $ext;
}

function export_csv(string $filename, array $headers, array $rows): void
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('X-Content-Type-Options: nosniff');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $headers);
    foreach ($rows as $row) {
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

function export_excel(string $filename, string $title, array $headers, array $rows): void
{
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('X-Content-Type-Options: nosniff');

    echo '<html><head><meta charset="utf-8"><title>' . e($title) . '</title></head><body>';
    echo '<table border="1"><thead><tr>';
    foreach ($headers as $header) {
        echo '<th>' . e((string) $header) . '</th>';
    }
    echo '</tr></thead><tbody>';
    foreach ($rows as $row) {
        echo '<tr>';
        foreach ($row as $cell) {
            echo '<td>' . e((string) $cell) . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table></body></html>';
    exit;
}

function report_dataset(string $type, array $filters): array
{
    $from = $filters['date_from'] ?? '';
    $to = $filters['date_to'] ?? '';

    switch ($type) {
        case 'residents':
            $stmt = db()->query(
                'SELECT resident_code, full_name, gender, phone, email, unit_number, status, created_at
                 FROM residents ORDER BY id DESC'
            );
            return [
                'title' => 'Resident report',
                'headers' => ['Code', 'Name', 'Gender', 'Phone', 'Email', 'Unit', 'Status', 'Created'],
                'rows' => $stmt->fetchAll(PDO::FETCH_NUM) ?: [],
            ];

        case 'visitors':
            return visitor_report_dataset($from, $to, null);

        case 'today':
            return visitor_report_dataset(today(), today(), null);

        case 'checkin':
            return visitor_report_dataset($from, $to, 'checked_in', true);

        case 'checkout':
            return visitor_report_dataset($from, $to, 'checked_out', false, true);

        case 'inside':
            $stmt = db()->query(
                "SELECT v.visitor_name, v.car_plate, v.phone, r.full_name, r.unit_number, v.checked_in_at
                 FROM visitors v
                 INNER JOIN residents r ON r.id = v.resident_id
                 WHERE v.status = 'checked_in'
                 ORDER BY v.checked_in_at DESC"
            );
            return [
                'title' => 'Currently inside',
                'headers' => ['Visitor', 'Plate', 'Phone', 'Host', 'Unit', 'Checked in'],
                'rows' => $stmt->fetchAll(PDO::FETCH_NUM) ?: [],
            ];

        case 'blacklist':
            $stmt = db()->query(
                'SELECT name, car_plate, phone, reason, is_active, created_at
                 FROM visitor_blacklist ORDER BY id DESC'
            );
            $rows = [];
            foreach ($stmt->fetchAll() ?: [] as $row) {
                $rows[] = [
                    $row['name'],
                    $row['car_plate'],
                    $row['phone'],
                    $row['reason'],
                    (int) $row['is_active'] === 1 ? 'Active' : 'Inactive',
                    $row['created_at'],
                ];
            }
            return [
                'title' => 'Blacklist report',
                'headers' => ['Name', 'Plate', 'Phone', 'Reason', 'Status', 'Created'],
                'rows' => $rows,
            ];

        case 'activity':
            $where = ['1=1'];
            $params = [];
            if ($from !== '') {
                $where[] = 'a.created_at >= :from';
                $params[':from'] = $from . ' 00:00:00';
            }
            if ($to !== '') {
                $where[] = 'a.created_at <= :to';
                $params[':to'] = $to . ' 23:59:59';
            }
            $sql = 'SELECT a.created_at, u.username, a.action, a.module, a.description
                    FROM activity_logs a
                    LEFT JOIN users u ON u.id = a.user_id
                    WHERE ' . implode(' AND ', $where) . '
                    ORDER BY a.id DESC
                    LIMIT 2000';
            $stmt = db()->prepare($sql);
            $stmt->execute($params);
            return [
                'title' => 'Activity report',
                'headers' => ['Time', 'User', 'Action', 'Module', 'Description'],
                'rows' => $stmt->fetchAll(PDO::FETCH_NUM) ?: [],
            ];

        default:
            return ['title' => 'Report', 'headers' => [], 'rows' => []];
    }
}

function visitor_report_dataset(string $from, string $to, ?string $status, bool $useCheckin = false, bool $useCheckout = false): array
{
    $where = ['1=1'];
    $params = [];

    if ($status) {
        $where[] = 'v.status = :status';
        $params[':status'] = $status;
    }
    if ($useCheckin) {
        if ($from !== '') {
            $where[] = 'v.checked_in_at >= :from';
            $params[':from'] = $from . ' 00:00:00';
        }
        if ($to !== '') {
            $where[] = 'v.checked_in_at <= :to';
            $params[':to'] = $to . ' 23:59:59';
        }
    } elseif ($useCheckout) {
        if ($from !== '') {
            $where[] = 'v.checked_out_at >= :from';
            $params[':from'] = $from . ' 00:00:00';
        }
        if ($to !== '') {
            $where[] = 'v.checked_out_at <= :to';
            $params[':to'] = $to . ' 23:59:59';
        }
    } else {
        if ($from !== '') {
            $where[] = 'v.visit_date >= :from';
            $params[':from'] = $from;
        }
        if ($to !== '') {
            $where[] = 'v.visit_date <= :to';
            $params[':to'] = $to;
        }
    }

    $stmt = db()->prepare(
        'SELECT v.visitor_name, v.car_plate, v.phone, v.purpose, v.visit_date, v.status,
                r.full_name, r.unit_number, v.checked_in_at, v.checked_out_at
         FROM visitors v
         INNER JOIN residents r ON r.id = v.resident_id
         WHERE ' . implode(' AND ', $where) . '
         ORDER BY v.id DESC'
    );
    $stmt->execute($params);

    return [
        'title' => 'Visitor report',
        'headers' => ['Visitor', 'Plate', 'Phone', 'Purpose', 'Visit date', 'Status', 'Host', 'Unit', 'Checked in', 'Checked out'],
        'rows' => $stmt->fetchAll(PDO::FETCH_NUM) ?: [],
    ];
}
