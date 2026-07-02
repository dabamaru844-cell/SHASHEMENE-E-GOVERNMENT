<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
requireModule('employees');

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT e.*, d.name AS department_name FROM employees e JOIN departments d ON e.department_id = d.id WHERE e.id = ?');
$stmt->execute([$id]);
$emp = $stmt->fetch();
if (!$emp) {
    flash('error', __('error_occurred'));
    redirect(baseUrl('modules/employees/index.php'));
}

$assets = $pdo->prepare(
    'SELECT a.*, aa.assignment_date FROM asset_assignments aa JOIN assets a ON aa.asset_id = a.id
     WHERE aa.employee_id = ? AND aa.status = "active"'
);
$assets->execute([$id]);
$assignedAssets = $assets->fetchAll();

$attendance = $pdo->prepare('SELECT * FROM attendance WHERE employee_id = ? ORDER BY attendance_date DESC LIMIT 10');
$attendance->execute([$id]);
$attendanceRecords = $attendance->fetchAll();

$pageTitle = $emp['first_name'] . ' ' . $emp['last_name'];
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-header d-flex justify-content-between align-items-center">
    <h1><?= e($emp['first_name'] . ' ' . $emp['last_name']) ?></h1>
    <?php if (hasRole('admin', 'hr')): ?>
    <a href="<?= baseUrl('modules/employees/edit.php?id=' . $id) ?>" class="btn btn-brand"><?= __('edit') ?></a>
    <?php endif; ?>
</div>
<div class="row g-3">
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <?php if ($emp['photo']): ?>
                <img src="<?= assetUrl($emp['photo']) ?>" alt="" class="profile-photo mb-3">
                <?php else: ?>
                <div class="profile-photo bg-secondary d-inline-flex align-items-center justify-content-center mb-3"><i class="bi bi-person fs-1 text-white"></i></div>
                <?php endif; ?>
                <h5><?= e($emp['employee_code']) ?></h5>
                <p class="text-muted mb-0"><?= e($emp['position']) ?></p>
                <span class="badge bg-primary mt-2"><?= e($emp['department_name']) ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card mb-3"><div class="card-header"><?= __('personal_info') ?></div><div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-2"><strong><?= __('gender') ?>:</strong> <?= e(__($emp['gender'])) ?></div>
                <div class="col-md-6 mb-2"><strong><?= __('date_of_birth') ?>:</strong> <?= formatDate($emp['date_of_birth']) ?></div>
                <div class="col-md-6 mb-2"><strong><?= __('employment_date') ?>:</strong> <?= formatDate($emp['employment_date']) ?></div>
                <div class="col-md-6 mb-2"><strong><?= __('phone') ?>:</strong> <?= e($emp['phone'] ?: '-') ?></div>
                <div class="col-md-6 mb-2"><strong><?= __('email') ?>:</strong> <?= e($emp['email'] ?: '-') ?></div>
                <div class="col-12 mb-2"><strong><?= __('address') ?>:</strong> <?= e($emp['address'] ?: '-') ?></div>
            </div>
        </div></div>
        <div class="card mb-3"><div class="card-header"><?= __('assigned_assets') ?></div><div class="card-body p-0">
            <table class="table table-sm mb-0"><thead><tr><th><?= __('asset_name') ?></th><th><?= __('category') ?></th><th><?= __('assignment_date') ?></th></tr></thead>
            <tbody><?php foreach ($assignedAssets as $a): ?><tr><td><?= e($a['name']) ?></td><td><?= e($a['asset_code']) ?></td><td><?= formatDate($a['assignment_date']) ?></td></tr><?php endforeach; ?>
            <?php if (empty($assignedAssets)): ?><tr><td colspan="3" class="text-center text-muted"><?= __('no_records') ?></td></tr><?php endif; ?></tbody></table>
        </div></div>
        <div class="card"><div class="card-header"><?= __('attendance_history') ?></div><div class="card-body p-0">
            <table class="table table-sm mb-0"><thead><tr><th><?= __('date') ?></th><th><?= __('check_in') ?></th><th><?= __('check_out') ?></th><th><?= __('status') ?></th></tr></thead>
            <tbody><?php foreach ($attendanceRecords as $a): ?><tr><td><?= formatDate($a['attendance_date']) ?></td><td><?= formatTime($a['check_in']) ?></td><td><?= formatTime($a['check_out']) ?></td><td><?= e(__($a['status'])) ?></td></tr><?php endforeach; ?>
            <?php if (empty($attendanceRecords)): ?><tr><td colspan="4" class="text-center text-muted"><?= __('no_records') ?></td></tr><?php endif; ?></tbody></table>
        </div></div>
    </div>
</div>
<a href="<?= baseUrl('modules/employees/index.php') ?>" class="btn btn-secondary mt-3"><?= __('back') ?></a>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
