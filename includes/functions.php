<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function baseUrl(string $path = ''): string
{
    static $base = null;
    if ($base === null) {
        $config = require __DIR__ . '/../config/app.php';
        $base = rtrim($config['url'], '/');
    }
    $path = ltrim($path, '/');
    return $path === '' ? $base : $base . '/' . $path;
}

function assetUrl(string $path): string
{
    return baseUrl('assets/' . ltrim($path, '/'));
}

function csrfToken(): string
{
    $config = require __DIR__ . '/../config/app.php';
    $name = $config['csrf_token_name'];
    if (empty($_SESSION[$name])) {
        $_SESSION[$name] = bin2hex(random_bytes(32));
    }
    return $_SESSION[$name];
}

function csrfField(): string
{
    $config = require __DIR__ . '/../config/app.php';
    $token = csrfToken();
    $name = e($config['csrf_token_name']);
    return '<input type="hidden" name="' . $name . '" value="' . e($token) . '">';
}

function verifyCsrf(): bool
{
    $config = require __DIR__ . '/../config/app.php';
    $name = $config['csrf_token_name'];
    $token = $_POST[$name] ?? '';
    return isset($_SESSION[$name]) && hash_equals($_SESSION[$name], $token);
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][$type][] = $message;
}

function getFlash(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function renderFlash(): string
{
    $messages = getFlash();
    if (empty($messages)) {
        return '';
    }
    $html = '';
    foreach ($messages as $type => $items) {
        $class = match ($type) {
            'success' => 'alert-success',
            'error', 'danger' => 'alert-danger',
            'warning' => 'alert-warning',
            default => 'alert-info',
        };
        foreach ($items as $msg) {
            $html .= '<div class="alert ' . $class . ' alert-dismissible fade show" role="alert">'
                . e($msg)
                . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        }
    }
    return $html;
}

function logActivity(PDO $pdo, ?int $userId, string $action, string $module, ?int $recordId = null, ?string $details = null): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO activity_logs (user_id, action, module, record_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $userId,
        $action,
        $module,
        $recordId,
        $details,
        $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}

function createNotification(PDO $pdo, ?int $userId, string $type, string $title, string $message, ?string $link = null): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$userId, $type, $title, $message, $link]);
}

function notifyRole(PDO $pdo, int $roleId, string $type, string $title, string $message, ?string $link = null): void
{
    $stmt = $pdo->prepare('SELECT id FROM users WHERE role_id = ? AND is_active = 1');
    $stmt->execute([$roleId]);
    while ($row = $stmt->fetch()) {
        createNotification($pdo, (int) $row['id'], $type, $title, $message, $link);
    }
}

function paginate(int $total, int $page, int $perPage = 15): array
{
    $totalPages = max(1, (int) ceil($total / $perPage));
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $perPage;
    return [
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => $totalPages,
        'offset' => $offset,
    ];
}

function renderPagination(array $pagination, string $baseQuery = ''): string
{
    if ($pagination['total_pages'] <= 1) {
        return '';
    }
    $sep = str_contains($baseQuery, '?') ? '&' : '?';
    $html = '<nav><ul class="pagination justify-content-center">';
    for ($i = 1; $i <= $pagination['total_pages']; $i++) {
        $active = $i === $pagination['page'] ? ' active' : '';
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . e($baseQuery . $sep . 'page=' . $i) . '">' . $i . '</a></li>';
    }
    $html .= '</ul></nav>';
    return $html;
}

function validateEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function uploadFile(array $file, string $subdir, array $allowedExtensions): ?string
{
    $config = require __DIR__ . '/../config/app.php';
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > $config['max_upload_size']) {
        return null;
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExtensions, true)) {
        return null;
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!isset($config['allowed_upload_types'][$ext]) || $config['allowed_upload_types'][$ext] !== $mime) {
        return null;
    }
    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $destDir = __DIR__ . '/../assets/uploads/' . trim($subdir, '/');
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }
    $destPath = $destDir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        return null;
    }
    return 'uploads/' . trim($subdir, '/') . '/' . $filename;
}

function formatDate(?string $date, string $format = 'd M Y'): string
{
    if (!$date) {
        return '-';
    }
    return date($format, strtotime($date));
}

function formatTime(?string $time): string
{
    if (!$time) {
        return '-';
    }
    return date('H:i', strtotime($time));
}

function exportCsv(string $filename, array $headers, array $rows): never
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($out, $headers);
    foreach ($rows as $row) {
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

function getDepartments(PDO $pdo): array
{
    return $pdo->query('SELECT id, name FROM departments WHERE is_active = 1 ORDER BY name')->fetchAll();
}

function getAssetCategories(PDO $pdo): array
{
    return $pdo->query('SELECT c.id, c.name, c.parent_id, p.name AS parent_name FROM asset_categories c LEFT JOIN asset_categories p ON c.parent_id = p.id ORDER BY COALESCE(c.parent_id, c.id), c.name')->fetchAll();
}

function getEmployeesList(PDO $pdo, bool $activeOnly = true): array
{
    $sql = 'SELECT e.id, e.employee_code, e.first_name, e.last_name, d.name AS department FROM employees e JOIN departments d ON e.department_id = d.id';
    if ($activeOnly) {
        $sql .= " WHERE e.employment_status = 'active'";
    }
    $sql .= ' ORDER BY e.last_name, e.first_name';
    return $pdo->query($sql)->fetchAll();
}

function generateEmployeeCode(PDO $pdo): string
{
    $year = date('Y');
    $stmt = $pdo->query("SELECT COUNT(*) FROM employees WHERE employee_code LIKE 'EMP{$year}%'");
    $count = (int) $stmt->fetchColumn() + 1;
    return sprintf('EMP%s%04d', $year, $count);
}

function generateAssetCode(PDO $pdo): string
{
    $year = date('Y');
    $stmt = $pdo->query("SELECT COUNT(*) FROM assets WHERE asset_code LIKE 'AST{$year}%'");
    $count = (int) $stmt->fetchColumn() + 1;
    return sprintf('AST%s%04d', $year, $count);
}

function calculateWorkingHours(?string $checkIn, ?string $checkOut): float
{
    if (!$checkIn || !$checkOut) {
        return 0.0;
    }
    $start = strtotime($checkIn);
    $end = strtotime($checkOut);
    if ($end <= $start) {
        return 0.0;
    }
    return round(($end - $start) / 3600, 2);
}

function getUnreadNotificationCount(PDO $pdo, int $userId): int
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}
