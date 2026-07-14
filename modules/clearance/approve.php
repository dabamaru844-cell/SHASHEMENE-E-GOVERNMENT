<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole('admin', 'hr', 'it');

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    flash('error', __('invalid_request'));
    redirect(baseUrl('modules/clearance/index.php'));
}

$stmt = $pdo->prepare("
    SELECT c.*, e.employee_code, e.first_name, e.last_name
    FROM clearances c
    JOIN employees e ON c.employee_id = e.id
    WHERE c.id = ?
");
$stmt->execute([$id]);
$clearance = $stmt->fetch();

if (!$clearance) {
    flash('error', __('clearance_not_found'));
    redirect(baseUrl('modules/clearance/index.php'));
}

// Determine which department the user can approve
$userRole = currentUser()['role_id'];
$canApproveDept = [];
if ($userRole == 1) { // Admin can approve all
    $canApproveDept = ['hr', 'it', 'finance', 'store', 'administration'];
} elseif ($userRole == 2) { // HR
    $canApproveDept = ['hr'];
} elseif ($userRole == 3) { // IT
    $canApproveDept = ['it'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $department = $_POST['department'] ?? '';
    $status = $_POST['status'] ?? '';
    $comments = trim($_POST['comments'] ?? '');
    $assetsReturned = trim($_POST['assets_returned'] ?? '');
    
    if (!in_array($department, $canApproveDept, true)) {
        flash('error', __('not_authorized_to_approve_this_department'));
        redirect(baseUrl('modules/clearance/view.php?id=' . $id));
    }
    
    if (!in_array($status, ['approved', 'rejected'], true)) {
        flash('error', __('invalid_status'));
        redirect(baseUrl('modules/clearance/view.php?id=' . $id));
    }
    
    try {
        $pdo->beginTransaction();
        
        // Update clearance department status
        $statusCol = $department . '_status';
        $updateStmt = $pdo->prepare("UPDATE clearances SET $statusCol = ?, overall_status = 'in_progress' WHERE id = ?");
        $updateStmt->execute([$status, $id]);
        
        // Update or insert approval record
        $approvalStmt = $pdo->prepare('
            UPDATE clearance_approvals 
            SET status = ?, approved_by = ?, comments = ?, assets_returned = ?, updated_at = NOW()
            WHERE clearance_id = ? AND department = ?
        ');
        $approvalStmt->execute([$status, currentUser()['id'], $comments, $assetsReturned, $id, $department]);
        
        // Check if all departments approved
        $checkStmt = $pdo->prepare("
            SELECT hr_status, it_status, finance_status, store_status, administration_status
            FROM clearances WHERE id = ?
        ");
        $checkStmt->execute([$id]);
        $statuses = $checkStmt->fetch();
        
        $allApproved = true;
        foreach ($statuses as $st) {
            if ($st !== 'approved') {
                $allApproved = false;
                break;
            }
        }
        
        if ($allApproved) {
            // Mark as completed and generate certificate
            $pdo->prepare('
                UPDATE clearances 
                SET overall_status = "completed", approved_by = ?, certificate_generated = 1
                WHERE id = ?
            ')->execute([currentUser()['id'], $id]);
            
            // Notify employee and HR
            notifyRole($pdo, 1, 'clearance', 'Clearance Completed', 
                "Clearance for {$clearance['first_name']} {$clearance['last_name']} has been completed", 
                baseUrl('modules/clearance/view.php?id=' . $id));
        }
        
        logActivity($pdo, (int) currentUser()['id'], 'approve', 'clearances', $id, 
            "$department: $status");
        
        $pdo->commit();
        flash('success', __('approval_recorded_successfully'));
        redirect(baseUrl('modules/clearance/view.php?id=' . $id));
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Clearance approval failed: ' . $e->getMessage());
        flash('error', __('error_occurred'));
    }
}

$pageTitle = __('approve_clearance');
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="bi bi-check-circle me-2"></i><?= __('approve_clearance') ?></h1>
        <a href="<?= baseUrl('modules/clearance/view.php?id=' . $id) ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i><?= __('back') ?>
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h5 class="mb-3"><?= __('employee_information') ?></h5>
        <dl class="row mb-4">
            <dt class="col-sm-3"><?= __('employee_id') ?>:</dt>
            <dd class="col-sm-9"><?= e($clearance['employee_code']) ?></dd>
            
            <dt class="col-sm-3"><?= __('employee_name') ?>:</dt>
            <dd class="col-sm-9"><?= e($clearance['first_name'] . ' ' . $clearance['last_name']) ?></dd>
            
            <dt class="col-sm-3"><?= __('exit_reason') ?>:</dt>
            <dd class="col-sm-9"><?= e(__($clearance['exit_reason'])) ?></dd>
            
            <dt class="col-sm-3"><?= __('exit_date') ?>:</dt>
            <dd class="col-sm-9"><?= formatDate($clearance['exit_date']) ?></dd>
        </dl>
        
        <hr>
        
        <h5 class="mb-3"><?= __('department_approval') ?></h5>
        
        <form method="post">
            <?= csrfField() ?>
            
            <div class="mb-3">
                <label class="form-label"><?= __('department') ?> <span class="text-danger">*</span></label>
                <select name="department" class="form-select" required>
                    <option value=""><?= __('select_department') ?></option>
                    <?php foreach ($canApproveDept as $dept): ?>
                        <?php
                        $statusKey = $dept . '_status';
                        $deptStatus = $clearance[$statusKey];
                        $disabled = $deptStatus !== 'pending' ? ' disabled' : '';
                        ?>
                        <option value="<?= $dept ?>"<?= $disabled ?>>
                            <?= e(__(ucfirst($dept))) ?> 
                            <?= $deptStatus !== 'pending' ? '(' . __($deptStatus) . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label"><?= __('approval_status') ?> <span class="text-danger">*</span></label>
                <div class="btn-group w-100" role="group">
                    <input type="radio" class="btn-check" name="status" id="approve" value="approved" required>
                    <label class="btn btn-outline-success" for="approve">
                        <i class="bi bi-check-circle me-1"></i><?= __('approve') ?>
                    </label>
                    
                    <input type="radio" class="btn-check" name="status" id="reject" value="rejected">
                    <label class="btn btn-outline-danger" for="reject">
                        <i class="bi bi-x-circle me-1"></i><?= __('reject') ?>
                    </label>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label"><?= __('assets_returned') ?></label>
                <textarea name="assets_returned" class="form-control" rows="3" 
                          placeholder="<?= e(__('list_assets_returned')) ?>"></textarea>
                <small class="text-muted"><?= __('assets_returned_help') ?></small>
            </div>
            
            <div class="mb-3">
                <label class="form-label"><?= __('comments') ?></label>
                <textarea name="comments" class="form-control" rows="3"></textarea>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check me-1"></i><?= __('submit_approval') ?>
                </button>
                <a href="<?= baseUrl('modules/clearance/view.php?id=' . $id) ?>" class="btn btn-secondary">
                    <?= __('cancel') ?>
                </a>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
