<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole('admin');

$id = (int) ($_GET['id'] ?? 0);
if ($id === (int) currentUser()['id']) {
    flash('error', __('error_occurred'));
    redirect(baseUrl('modules/users/index.php'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
    logActivity($pdo, (int) currentUser()['id'], 'delete', 'users', $id);
    flash('success', __('deleted_success'));
}

redirect(baseUrl('modules/users/index.php'));
