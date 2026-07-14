<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole('admin', 'hr');

$employeeId = (int) ($_GET['id'] ?? 0);

if ($employeeId <= 0) {
    flash('error', __('invalid_request'));
    redirect(baseUrl('modules/retirement/index.php'));
}

// Get employee and retirement details
$stmt = $pdo->prepare("
    SELECT e.*, d.name AS department_name, r.retirement_date, r.status AS retirement_status, r.remarks,
           TIMESTAMPDIFF(YEAR, e.date_of_birth, CURDATE()) AS current_age,
           TIMESTAMPDIFF(DAY, CURDATE(), r.retirement_date) AS days_until_retirement
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

$pageTitle = __('retirement_details');
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="bi bi-person-badge me-2"></i><?= __('retirement_details') ?></h1>
        <a href="<?= baseUrl('modules/retirement/index.php') ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i><?= __('back') ?>
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <?php if ($employee['photo']): ?>
                    <img src="<?= assetUrl($employee['photo']) ?>" alt="Photo" class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                <?php else: ?>
                    <div class="bg-secondary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 150px; height: 150px;">
                        <i class="bi bi-person-fill text-white" style="font-size: 4rem;"></i>
                    </div>
                <?php endif; ?>
                <h4><?= e($employee['first_name'] . ' ' . $employee['last_name']) ?></h4>
                <p class="text-muted"><?= e($employee['employee_code']) ?></p>
                <?php
                $badge = match($employee['retirement_status'] ?? 'active') {
                    'near_retirement' => 'bg-warning',
                    'retirement_eligible' => 'bg-danger',
                    'retired' => 'bg-secondary',
                    default => 'bg-success'
                };
                ?>
                <span class="badge <?= $badge ?> mb-3">
                    <?= e(__($employee['retirement_status'] ?? 'active')) ?>
                </span>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i><?= __('personal_info') ?></h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <strong><?= __('department') ?>:</strong><br>
                        <?= e($employee['department_name']) ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong><?= __('position') ?>:</strong><br>
                        <?= e($employee['position']) ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong><?= __('date_of_birth') ?>:</strong><br>
                        <?= formatDate($employee['date_of_birth']) ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong><?= __('current_age') ?>:</strong><br>
                        <?= (int) $employee['current_age'] ?> <?= __('years') ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong><?= __('employment_date') ?>:</strong><br>
                        <?= formatDate($employee['employment_date']) ?>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong><?= __('phone') ?>:</strong><br>
                        <?= e($employee['phone'] ?: '-') ?>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($employee['retirement_date']): ?>
        <div class="card mb-3">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="bi bi-calendar-event me-2"></i><?= __('retirement_information') ?></h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <strong><?= __('retirement_date') ?>:</strong><br>
                        <span class="text-danger fs-5"><?= formatDate($employee['retirement_date']) ?></span>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong><?= __('days_until_retirement') ?>:</strong><br>
                        <?php if ($employee['days_until_retirement'] > 0): ?>
                            <span class="text-warning fs-5"><?= (int) $employee['days_until_retirement'] ?> <?= __('days') ?></span>
                        <?php else: ?>
                            <span class="text-danger fs-5"><?= __('retirement_due') ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($employee['remarks']): ?>
                    <div class="col-12 mb-3">
                        <strong><?= __('remarks') ?>:</strong><br>
                        <?= e($employee['remarks']) ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($employee['retirement_status'] === 'retirement_eligible'): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong><?= __('action_required') ?>:</strong> 
                    <?= __('employee_reached_retirement_age') ?>
                </div>
                <a href="<?= baseUrl('modules/retirement/process.php?id=' . $employeeId) ?>" class="btn btn-success">
                    <i class="bi bi-check-circle me-1"></i><?= __('process_retirement') ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
