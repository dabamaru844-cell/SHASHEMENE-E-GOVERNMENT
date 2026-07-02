<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
requireModule('assets');

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT a.*, c.name AS category_name FROM assets a JOIN asset_categories c ON a.category_id = c.id WHERE a.id = ?');
$stmt->execute([$id]);
$asset = $stmt->fetch();
if (!$asset) redirect(baseUrl('modules/assets/index.php'));

$assignment = $pdo->prepare(
    'SELECT aa.*, e.first_name, e.last_name, e.employee_code FROM asset_assignments aa
     JOIN employees e ON aa.employee_id = e.id WHERE aa.asset_id = ? AND aa.status = "active"'
);
$assignment->execute([$id]);
$currentAssignment = $assignment->fetch();

$pageTitle = $asset['name'];
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-header d-flex justify-content-between"><h1><?= e($asset['name']) ?></h1>
<?php if (hasRole('admin', 'it')): ?><a href="<?= baseUrl('modules/assets/edit.php?id=' . $id) ?>" class="btn btn-brand"><?= __('edit') ?></a><?php endif; ?>
</div>
<div class="row g-3">
    <div class="col-md-8">
        <div class="card"><div class="card-header"><?= __('asset_name') ?>: <?= e($asset['asset_code']) ?></div><div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-2"><strong><?= __('category') ?>:</strong> <?= e($asset['category_name']) ?></div>
                <div class="col-md-6 mb-2"><strong><?= __('brand') ?>:</strong> <?= e($asset['brand'] ?: '-') ?></div>
                <div class="col-md-6 mb-2"><strong><?= __('model') ?>:</strong> <?= e($asset['model'] ?: '-') ?></div>
                <div class="col-md-6 mb-2"><strong><?= __('serial_number') ?>:</strong> <?= e($asset['serial_number'] ?: '-') ?></div>
                <div class="col-md-6 mb-2"><strong><?= __('purchase_date') ?>:</strong> <?= formatDate($asset['purchase_date']) ?></div>
                <div class="col-md-6 mb-2"><strong><?= __('warranty_expiry') ?>:</strong> <?= formatDate($asset['warranty_expiry']) ?></div>
                <div class="col-md-6 mb-2"><strong><?= __('cost') ?>:</strong> <?= number_format((float) $asset['cost'], 2) ?></div>
                <div class="col-md-6 mb-2"><strong><?= __('status') ?>:</strong> <?= e(__($asset['status'])) ?></div>
                <div class="col-md-6 mb-2"><strong><?= __('location') ?>:</strong> <?= e($asset['location'] ?: '-') ?></div>
                <div class="col-12"><strong><?= __('description') ?>:</strong> <?= e($asset['description'] ?: '-') ?></div>
            </div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card"><div class="card-header"><?= __('assigned') ?></div><div class="card-body">
            <?php if ($currentAssignment): ?>
            <p><strong><?= e($currentAssignment['employee_code']) ?></strong><br><?= e($currentAssignment['first_name'] . ' ' . $currentAssignment['last_name']) ?></p>
            <small class="text-muted"><?= __('assignment_date') ?>: <?= formatDate($currentAssignment['assignment_date']) ?></small>
            <?php else: ?><p class="text-muted mb-0"><?= __('no_records') ?></p><?php endif; ?>
        </div></div>
    </div>
</div>
<a href="<?= baseUrl('modules/assets/index.php') ?>" class="btn btn-secondary mt-3"><?= __('back') ?></a>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
