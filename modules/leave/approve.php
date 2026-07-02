<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole('admin', 'hr');

$id = (int) ($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';
if (!in_array($action, ['approved', 'rejected'], true)) {
    redirect(baseUrl('modules/leave/index.php'));
}

$pdo->prepare('UPDATE leave_requests SET approval_status = ?, approved_by = ?, approved_at = NOW() WHERE id = ?')
    ->execute([$action, (int) currentUser()['id'], $id]);
logActivity($pdo, (int) currentUser()['id'], $action, 'leave', $id);
flash('success', __('updated_success'));
redirect(baseUrl('modules/leave/index.php'));
