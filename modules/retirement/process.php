<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole('admin', 'hr');

$employeeId = (int) ($_GET['id'] ?? 0);

if ($employeeId <= 0) {
    flash('error', __('invalid_request'));
    redirect(baseUrl('modules/retirement/index.php'));
}

// Get employee details
$stmt = $pdo->prepare("
    SELECT e.*, d.name AS department_name, r.status AS retirement_status
    FROM employees e
    JOIN departments d ON e.department_id = d.id
    LEFT JOIN retirements r ON e.id = r.employee_id
    WHERE e.id = ?
");
$stmt->execute([$employeeId]);
$employee = $stmt->fetch();

if (!$employee) {
    flash('error', __('employee_not_found'));
    redirect(baseUrl('modules/retirement/index.php'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $action = $_POST['action'] ?? '';
    $remarks = trim($_POST['remarks'] ?? '');
    
    try {
        $pdo->beginTransaction();
        
        if ($action === 'retire') {
            // Update retirement status
            $pdo->prepare('UPDATE retirements SET status = ?, remarks = ? WHERE employee_id = ?')
                ->execute(['retired', $remarks, $employeeId]);
            
            // Update employee status
            $pdo->prepare('UPDATE employees SET employment_status = ? WHERE id = ?')
                ->execute(['terminated', $employeeId]);
            
            // Log activity
            logActivity($pdo, (int) currentUser()['id'], 'retire', 'employees', $employeeId, 
                "Processed retirement for {$employee['first_name']} {$employee['last_name']}");
            
            // Create notification
            notifyRole($pdo, 1, 'retirement', 'Employee Retired', 
                "Employee {$employee['first_name']} {$employee['last_name']} has been retired", 
                baseUrl('modules/retirement/view.php?id=' . $employeeId));
            
            $pdo->commit();
            flash('success', __('retirement_processed_successfully'));
            redirect(baseUrl('modules/retirement/index.php'));
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Retirement processing failed: ' . $e->getMessage());
        flash('error', __('error_occurred'));
    }
}

$pageTitle = __('process_retirement');
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="bi bi-check-circle me-2"></i><?= __('process_retirement') ?></h1>
        <a href="<?= baseUrl('modules/retirement/view.php?id=' . $employeeId) ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i><?= __('back') ?>
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong><?= __('warning') ?>:</strong> <?= __('retirement_process_warning') ?>
        </div>
        
        <h5 class="mb-3"><?= __('employee_information') ?></h5>
        <dl class="row">
            <dt class="col-sm-3"><?= __('employee_id') ?>:</dt>
            <dd class="col-sm-9"><?= e($employee['employee_code']) ?></dd>
            
            <dt class="col-sm-3"><?= __('employee_name') ?>:</dt>
            <dd class="col-sm-9"><?= e($employee['first_name'] . ' ' . $employee['last_name']) ?></dd>
            
            <dt class="col-sm-3"><?= __('department') ?>:</dt>
            <dd class="col-sm-9"><?= e($employee['department_name']) ?></dd>
            
            <dt class="col-sm-3"><?= __('position') ?>:</dt>
            <dd class="col-sm-9"><?= e($employee['position']) ?></dd>
            
            <dt class="col-sm-3"><?= __('current_status') ?>:</dt>
            <dd class="col-sm-9">
                <span class="badge bg-danger"><?= e(__($employee['retirement_status'])) ?></span>
            </dd>
        </dl>
        
        <hr>
        
        <form method="post">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="retire">
            
            <div class="mb-3">
                <label class="form-label"><?= __('remarks') ?></label>
                <textarea name="remarks" class="form-control" rows="4" 
                          placeholder="<?= e(__('retirement_remarks_placeholder')) ?>"></textarea>
            </div>
            
            <div class="alert alert-info">
                <strong><?= __('note') ?>:</strong>
                <ul class="mb-0">
                    <li><?= __('retirement_note_1') ?></li>
                    <li><?= __('retirement_note_2') ?></li>
                    <li><?= __('retirement_note_3') ?></li>
                </ul>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-circle me-1"></i><?= __('confirm_retirement') ?>
                </button>
                <a href="<?= baseUrl('modules/retirement/view.php?id=' . $employeeId) ?>" class="btn btn-secondary">
                    <?= __('cancel') ?>
                </a>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
