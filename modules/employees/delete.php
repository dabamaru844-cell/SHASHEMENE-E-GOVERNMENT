<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole('admin', 'hr');

$id = (int) ($_GET['id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $pdo->prepare('DELETE FROM employees WHERE id = ?')->execute([$id]);
    logActivity($pdo, (int) currentUser()['id'], 'delete', 'employees', $id);
    flash('success', __('deleted_success'));
}
redirect(baseUrl('modules/employees/index.php'));
