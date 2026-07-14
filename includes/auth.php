<?php

declare(strict_types=1);

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function isLoggedIn(): bool
{
    return currentUser() !== null;
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        flash('error', __('login_required'));
        redirect(baseUrl('login.php'));
    }
}

function hasRole(string ...$roles): bool
{
    $user = currentUser();
    if (!$user) {
        return false;
    }
    return in_array($user['role_slug'], $roles, true);
}

function requireRole(string ...$roles): void
{
    requireLogin();
    if (!hasRole(...$roles)) {
        flash('error', __('access_denied'));
        redirect(baseUrl('modules/dashboard/index.php'));
    }
}

function canAccess(string $module): bool
{
    $user = currentUser();
    if (!$user) {
        return false;
    }
    $permissions = [
        'admin' => ['dashboard', 'users', 'employees', 'assets', 'attendance', 'leave', 'retirement', 'clearance', 'reports', 'notifications', 'settings', 'profile'],
        'hr' => ['dashboard', 'employees', 'attendance', 'leave', 'retirement', 'clearance', 'reports', 'notifications', 'profile'],
        'it' => ['dashboard', 'assets', 'clearance', 'reports', 'notifications', 'profile'],
        'employee' => ['dashboard', 'profile', 'attendance', 'leave', 'notifications'],
    ];
    $role = $user['role_slug'];
    return isset($permissions[$role]) && in_array($module, $permissions[$role], true);
}

function requireModule(string $module): void
{
    requireLogin();
    if (!canAccess($module)) {
        flash('error', __('access_denied'));
        redirect(baseUrl('modules/dashboard/index.php'));
    }
}

function loginUser(PDO $pdo, string $username, string $password): bool
{
    $stmt = $pdo->prepare(
        'SELECT u.*, r.slug AS role_slug, r.name AS role_name
         FROM users u JOIN roles r ON u.role_id = r.id
         WHERE (u.username = ? OR u.email = ?) AND u.is_active = 1'
    );
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['password'])) {
        return false;
    }
    unset($user['password']);
    $_SESSION['user'] = $user;
    $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')->execute([$user['id']]);
    logActivity($pdo, (int) $user['id'], 'login', 'auth');
    session_regenerate_id(true);
    return true;
}

function logoutUser(PDO $pdo): void
{
    if ($user = currentUser()) {
        logActivity($pdo, (int) $user['id'], 'logout', 'auth');
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function registerUser(PDO $pdo, array $data): int
{
    $hash = password_hash($data['password'], PASSWORD_BCRYPT);
    $stmt = $pdo->prepare(
        'INSERT INTO users (username, email, password, role_id, employee_id) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $data['username'],
        $data['email'],
        $hash,
        $data['role_id'],
        $data['employee_id'] ?? null,
    ]);
    return (int) $pdo->lastInsertId();
}

function changePassword(PDO $pdo, int $userId, string $currentPassword, string $newPassword): bool
{
    $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($currentPassword, $user['password'])) {
        return false;
    }
    $hash = password_hash($newPassword, PASSWORD_BCRYPT);
    $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$hash, $userId]);
    logActivity($pdo, $userId, 'change_password', 'auth');
    return true;
}

function getRoles(PDO $pdo): array
{
    return $pdo->query('SELECT id, name, slug FROM roles ORDER BY id')->fetchAll();
}
