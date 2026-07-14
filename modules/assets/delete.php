<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole('admin', 'it');

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    flash('error', __('invalid_request'));
    redirect(baseUrl('modules/assets/index.php'));
}

// Check if asset exists
$stmt = $pdo->prepare('SELECT id, asset_code, name, status FROM assets WHERE id = ?');
$stmt->execute([$id]);
$asset = $stmt->fetch();

if (!$asset) {
    flash('error', __('asset_not_found'));
    redirect(baseUrl('modules/assets/index.php'));
}

// Check if asset is assigned
if ($asset['status'] === 'assigned') {
    flash('error', __('cannot_delete_assigned_asset'));
    redirect(baseUrl('modules/assets/index.php'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Delete related records first
        $pdo->prepare('DELETE FROM asset_assignments WHERE asset_id = ?')->execute([$id]);
        
        // Delete the asset
        $pdo->prepare('DELETE FROM assets WHERE id = ?')->execute([$id]);
        
        // Commit transaction
        $pdo->commit();
        
        logActivity($pdo, (int) currentUser()['id'], 'delete', 'assets', $id, 
            "Deleted asset: {$asset['asset_code']} - {$asset['name']}");
        flash('success', __('deleted_success'));
    } catch (PDOException $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Asset deletion failed: ' . $e->getMessage());
        flash('error', __('delete_failed') . ' ' . $e->getMessage());
    }
    redirect(baseUrl('modules/assets/index.php'));
}

// Show confirmation page
$pageTitle = __('delete_asset');
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-header">
    <h1><?= __('delete_asset') ?></h1>
</div>
<div class="card">
    <div class="card-body">
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?= __('delete_confirm') ?>
        </div>
        <p><strong><?= __('asset_code') ?>:</strong> <?= e($asset['asset_code']) ?></p>
        <p><strong><?= __('asset_name') ?>:</strong> <?= e($asset['name']) ?></p>
        <p><strong><?= __('status') ?>:</strong> <span class="badge bg-<?= match($asset['status']) { 'active'=>'success','maintenance'=>'warning','retired'=>'secondary','lost'=>'danger', default=>'secondary' } ?>"><?= e(__($asset['status'])) ?></span></p>
        <form method="post">
            <?= csrfField() ?>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-trash me-1"></i><?= __('delete') ?>
                </button>
                <a href="<?= baseUrl('modules/assets/index.php') ?>" class="btn btn-secondary">
                    <?= __('cancel') ?>
                </a>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
