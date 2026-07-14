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
    SELECT c.*, e.employee_code, e.first_name, e.last_name, e.position, e.photo,
           d.name AS department_name,
           u.username AS approved_by_name
    FROM clearances c
    JOIN employees e ON c.employee_id = e.id
    JOIN departments d ON e.department_id = d.id
    LEFT JOIN users u ON c.approved_by = u.id
    WHERE c.id = ?
");
$stmt->execute([$id]);
$clearance = $stmt->fetch();

if (!$clearance) {
    flash('error', __('clearance_not_found'));
    redirect(baseUrl('modules/clearance/index.php'));
}

$pageTitle = __('clearance_details');
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="bi bi-clipboard-check me-2"></i><?= __('clearance_details') ?></h1>
        <div class="btn-group">
            <?php if ($clearance['overall_status'] !== 'completed' && hasRole('admin', 'hr')): ?>
            <a href="<?= baseUrl('modules/clearance/approve.php?id=' . $id) ?>" class="btn btn-primary">
                <i class="bi bi-check-circle me-1"></i><?= __('approve_clearance') ?>
            </a>
            <?php endif; ?>
            <?php if ($clearance['overall_status'] === 'completed' && $clearance['certificate_generated']): ?>
            <a href="<?= baseUrl('modules/clearance/certificate.php?id=' . $id) ?>" class="btn btn-success" target="_blank">
                <i class="bi bi-file-earmark-pdf me-1"></i><?= __('view_certificate') ?>
            </a>
            <?php endif; ?>
            <a href="<?= baseUrl('modules/clearance/index.php') ?>" class="btn btn-secondary">
                <?= __('back') ?>
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-body text-center">
                <?php if ($clearance['photo']): ?>
                    <img src="<?= assetUrl($clearance['photo']) ?>" alt="Photo" class="rounded-circle mb-3" style="width: 120px; height: 120px; object-fit: cover;">
                <?php else: ?>
                    <div class="bg-secondary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 120px; height: 120px;">
                        <i class="bi bi-person-fill text-white" style="font-size: 3rem;"></i>
                    </div>
                <?php endif; ?>
                <h5><?= e($clearance['first_name'] . ' ' . $clearance['last_name']) ?></h5>
                <p class="text-muted mb-1"><?= e($clearance['employee_code']) ?></p>
                <p class="text-muted"><?= e($clearance['department_name']) ?></p>
                <?php
                $badge = match($clearance['overall_status']) {
                    'pending' => 'bg-warning',
                    'in_progress' => 'bg-info',
                    'completed' => 'bg-success',
                    'cancelled' => 'bg-secondary',
                    default => 'bg-secondary'
                };
                ?>
                <span class="badge <?= $badge ?>"><?= e(__($clearance['overall_status'])) ?></span>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><?= __('clearance_info') ?></h6>
            </div>
            <div class="card-body">
                <dl class="mb-0">
                    <dt><?= __('exit_reason') ?>:</dt>
                    <dd><?= e(__($clearance['exit_reason'])) ?></dd>
                    
                    <dt><?= __('exit_date') ?>:</dt>
                    <dd><?= formatDate($clearance['exit_date']) ?></dd>
                    
                    <dt><?= __('created_date') ?>:</dt>
                    <dd><?= formatDate($clearance['created_at']) ?></dd>
                    
                    <?php if ($clearance['remarks']): ?>
                    <dt><?= __('remarks') ?>:</dt>
                    <dd><?= e($clearance['remarks']) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><?= __('clearance_progress') ?></h5>
            </div>
            <div class="card-body">
                <?php
                $departments = [
                    'hr' => ['icon' => 'people', 'label' => __('human_resources')],
                    'it' => ['icon' => 'laptop', 'label' => __('it_department')],
                    'finance' => ['icon' => 'cash-coin', 'label' => __('finance')],
                    'store' => ['icon' => 'box-seam', 'label' => __('store_warehouse')],
                    'administration' => ['icon' => 'building', 'label' => __('administration')]
                ];
                
                $approved = 0;
                foreach ($departments as $key => $dept) {
                    $statusKey = $key . '_status';
                    if ($clearance[$statusKey] === 'approved') $approved++;
                }
                $percentage = round(($approved / count($departments)) * 100);
                ?>
                
                <div class="mb-4">
                    <h6><?= __('overall_progress') ?>: <?= $percentage ?>%</h6>
                    <div class="progress" style="height: 30px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" 
                             style="width: <?= $percentage ?>%" 
                             aria-valuenow="<?= $percentage ?>" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                            <?= $percentage ?>%
                        </div>
                    </div>
                </div>
                
                <div class="list-group">
                    <?php foreach ($departments as $key => $dept): ?>
                        <?php
                        $statusKey = $key . '_status';
                        $status = $clearance[$statusKey];
                        $icon = match($status) {
                            'approved' => 'check-circle-fill text-success',
                            'rejected' => 'x-circle-fill text-danger',
                            default => 'clock text-warning'
                        };
                        ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-<?= $dept['icon'] ?> me-2"></i>
                                <strong><?= $dept['label'] ?></strong>
                            </div>
                            <span>
                                <i class="bi bi-<?= $icon ?> me-1"></i>
                                <?= e(__($status)) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <?php if ($clearance['overall_status'] === 'completed'): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill me-2"></i>
            <strong><?= __('clearance_completed') ?>!</strong> 
            <?= __('all_departments_approved') ?>
            <?php if ($clearance['approved_by_name']): ?>
                <br><?= __('approved_by') ?>: <?= e($clearance['approved_by_name']) ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
