<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole('admin', 'hr');

$employees = getEmployeesList($pdo, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $employeeId = (int) ($_POST['employee_id'] ?? 0);
    $exitReason = $_POST['exit_reason'] ?? '';
    $exitDate = $_POST['exit_date'] ?? '';
    $remarks = trim($_POST['remarks'] ?? '');
    
    $errors = [];
    
    if ($employeeId <= 0) {
        $errors[] = __('please_select_employee');
    }
    
    if (!in_array($exitReason, ['resignation', 'retirement', 'termination', 'contract_end', 'other'], true)) {
        $errors[] = __('invalid_exit_reason');
    }
    
    if (empty($exitDate)) {
        $errors[] = __('exit_date_required');
    }
    
    // Check if clearance already exists
    $checkStmt = $pdo->prepare('SELECT id FROM clearances WHERE employee_id = ? AND overall_status != "completed"');
    $checkStmt->execute([$employeeId]);
    if ($checkStmt->fetch()) {
        $errors[] = __('clearance_already_exists');
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Create clearance record
            $stmt = $pdo->prepare('
                INSERT INTO clearances (employee_id, exit_reason, exit_date, remarks, overall_status)
                VALUES (?, ?, ?, ?, "pending")
            ');
            $stmt->execute([$employeeId, $exitReason, $exitDate, $remarks]);
            $clearanceId = (int) $pdo->lastInsertId();
            
            // Update employee status
            $pdo->prepare('UPDATE employees SET employment_status = ? WHERE id = ?')
                ->execute(['inactive', $employeeId]);
            
            // Create clearance approval records for each department
            $departments = ['hr', 'it', 'finance', 'store', 'administration'];
            $approvalStmt = $pdo->prepare('
                INSERT INTO clearance_approvals (clearance_id, department, status)
                VALUES (?, ?, "pending")
            ');
            foreach ($departments as $dept) {
                $approvalStmt->execute([$clearanceId, $dept]);
            }
            
            // Log activity
            logActivity($pdo, (int) currentUser()['id'], 'create', 'clearances', $clearanceId);
            
            // Notify departments
            $empStmt = $pdo->prepare('SELECT first_name, last_name FROM employees WHERE id = ?');
            $empStmt->execute([$employeeId]);
            $empData = $empStmt->fetch();
            $name = $empData['first_name'] . ' ' . $empData['last_name'];
            
            notifyRole($pdo, 1, 'clearance', 'New Clearance Request', 
                "Clearance request created for $name", 
                baseUrl('modules/clearance/view.php?id=' . $clearanceId));
            notifyRole($pdo, 2, 'clearance', 'New Clearance Request', 
                "Clearance request created for $name", 
                baseUrl('modules/clearance/view.php?id=' . $clearanceId));
            notifyRole($pdo, 3, 'clearance', 'New Clearance Request', 
                "Clearance request created for $name", 
                baseUrl('modules/clearance/view.php?id=' . $clearanceId));
            
            $pdo->commit();
            flash('success', __('clearance_created_successfully'));
            redirect(baseUrl('modules/clearance/view.php?id=' . $clearanceId));
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Clearance creation failed: ' . $e->getMessage());
            flash('error', __('error_occurred'));
        }
    } else {
        foreach ($errors as $error) {
            flash('error', $error);
        }
    }
}

$pageTitle = __('create_clearance');
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="bi bi-plus-circle me-2"></i><?= __('create_clearance') ?></h1>
        <a href="<?= baseUrl('modules/clearance/index.php') ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i><?= __('back') ?>
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="post">
            <?= csrfField() ?>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?= __('employee') ?> <span class="text-danger">*</span></label>
                    <select name="employee_id" class="form-select" required>
                        <option value=""><?= __('select_employee') ?></option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?= (int) $emp['id'] ?>">
                                <?= e($emp['employee_code'] . ' - ' . $emp['first_name'] . ' ' . $emp['last_name'] . ' (' . $emp['department'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?= __('exit_reason') ?> <span class="text-danger">*</span></label>
                    <select name="exit_reason" class="form-select" required>
                        <option value=""><?= __('select') ?></option>
                        <option value="resignation"><?= __('resignation') ?></option>
                        <option value="retirement"><?= __('retirement') ?></option>
                        <option value="termination"><?= __('termination') ?></option>
                        <option value="contract_end"><?= __('contract_end') ?></option>
                        <option value="other"><?= __('other') ?></option>
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?= __('exit_date') ?> <span class="text-danger">*</span></label>
                    <input type="date" name="exit_date" class="form-control" required>
                </div>
                
                <div class="col-12 mb-3">
                    <label class="form-label"><?= __('remarks') ?></label>
                    <textarea name="remarks" class="form-control" rows="4"></textarea>
                </div>
            </div>
            
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                <strong><?= __('note') ?>:</strong> <?= __('clearance_creation_note') ?>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check me-1"></i><?= __('create_clearance') ?>
                </button>
                <a href="<?= baseUrl('modules/clearance/index.php') ?>" class="btn btn-secondary">
                    <?= __('cancel') ?>
                </a>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
