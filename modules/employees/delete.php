<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole('admin', 'hr');

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    flash('error', __('invalid_request'));
    redirect(baseUrl('modules/employees/index.php'));
}

// Check if employee exists
$stmt = $pdo->prepare('SELECT id, first_name, last_name FROM employees WHERE id = ?');
$stmt->execute([$id]);
$employee = $stmt->fetch();

if (!$employee) {
    flash('error', __('employee_not_found'));
    redirect(baseUrl('modules/employees/index.php'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Delete related records first (to avoid foreign key constraints)
        // 1. Delete attendance records
        $pdo->prepare('DELETE FROM attendance WHERE employee_id = ?')->execute([$id]);
        
        // 2. Delete leave requests
        $pdo->prepare('DELETE FROM leave_requests WHERE employee_id = ?')->execute([$id]);
        
        // 3. Delete asset assignments
        $pdo->prepare('DELETE FROM asset_assignments WHERE employee_id = ?')->execute([$id]);
        
        // 4. Update users table (set employee_id to NULL if this employee has a user account)
        $pdo->prepare('UPDATE users SET employee_id = NULL WHERE employee_id = ?')->execute([$id]);
        
        // 5. Delete the employee
        $pdo->prepare('DELETE FROM employees WHERE id = ?')->execute([$id]);
        
        // Commit transaction
        $pdo->commit();
        
        logActivity($pdo, (int) currentUser()['id'], 'delete', 'employees', $id, 
            "Deleted employee: {$employee['first_name']} {$employee['last_name']}");
        flash('success', __('deleted_success'));
    } catch (PDOException $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Employee deletion failed: ' . $e->getMessage());
        flash('error', __('delete_failed') . ' ' . $e->getMessage());
    }
    redirect(baseUrl('modules/employees/index.php'));
}

// Show confirmation page
$pageTitle = __('delete_employee');
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-header">
    <h1><?= __('delete_employee') ?></h1>
</div>
<div class="card">
    <div class="card-body">
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?= __('delete_confirm') ?>
        </div>
        <p><strong><?= __('employee') ?>:</strong> <?= e($employee['first_name'] . ' ' . $employee['last_name']) ?></p>
        <form method="post">
            <?= csrfField() ?>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-trash me-1"></i><?= __('delete') ?>
                </button>
                <a href="<?= baseUrl('modules/employees/index.php') ?>" class="btn btn-secondary">
                    <?= __('cancel') ?>
                </a>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
