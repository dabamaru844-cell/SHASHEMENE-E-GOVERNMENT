<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole('admin', 'it');

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM assets WHERE id = ?');
$stmt->execute([$id]);
$asset = $stmt->fetch();
if (!$asset) redirect(baseUrl('modules/assets/index.php'));

$categories = getAssetCategories($pdo);
$departments = getDepartments($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $doc = $asset['document_path'];
    if (!empty($_FILES['document']['name'])) {
        $uploaded = uploadFile($_FILES['document'], 'documents', ['pdf','doc','docx','jpg','jpeg','png']);
        if ($uploaded) $doc = $uploaded;
    }
    $pdo->prepare(
        'UPDATE assets SET name=?, category_id=?, brand=?, model=?, serial_number=?, purchase_date=?, warranty_expiry=?, supplier=?, cost=?, status=?, location=?, description=?, document_path=?, department_id=? WHERE id=?'
    )->execute([
        trim($_POST['name'] ?? ''), (int) ($_POST['category_id'] ?? 0), trim($_POST['brand'] ?? ''),
        trim($_POST['model'] ?? ''), trim($_POST['serial_number'] ?? ''), $_POST['purchase_date'] ?: null,
        $_POST['warranty_expiry'] ?: null, trim($_POST['supplier'] ?? ''), (float) ($_POST['cost'] ?? 0),
        $_POST['status'] ?? 'active', trim($_POST['location'] ?? ''), trim($_POST['description'] ?? ''),
        $doc, !empty($_POST['department_id']) ? (int) $_POST['department_id'] : null, $id,
    ]);
    logActivity($pdo, (int) currentUser()['id'], 'update', 'assets', $id);
    flash('success', __('updated_success'));
    redirect(baseUrl('modules/assets/view.php?id=' . $id));
}

$pageTitle = __('edit') . ' - ' . $asset['asset_code'];
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-header"><h1><?= __('edit') ?> <?= e($asset['asset_code']) ?></h1></div>
<div class="card"><div class="card-body">
    <form method="post" enctype="multipart/form-data"><?= csrfField() ?>
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label"><?= __('asset_name') ?></label><input type="text" name="name" class="form-control" required value="<?= e($asset['name']) ?>"></div>
            <div class="col-md-6"><label class="form-label"><?= __('category') ?></label><select name="category_id" class="form-select" required><?php foreach ($categories as $c): ?><option value="<?= (int) $c['id'] ?>"<?= (int) $c['id'] === (int) $asset['category_id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label class="form-label"><?= __('brand') ?></label><input type="text" name="brand" class="form-control" value="<?= e($asset['brand'] ?? '') ?>"></div>
            <div class="col-md-4"><label class="form-label"><?= __('model') ?></label><input type="text" name="model" class="form-control" value="<?= e($asset['model'] ?? '') ?>"></div>
            <div class="col-md-4"><label class="form-label"><?= __('serial_number') ?></label><input type="text" name="serial_number" class="form-control" value="<?= e($asset['serial_number'] ?? '') ?>"></div>
            <div class="col-md-4"><label class="form-label"><?= __('purchase_date') ?></label><input type="date" name="purchase_date" class="form-control" value="<?= e($asset['purchase_date'] ?? '') ?>"></div>
            <div class="col-md-4"><label class="form-label"><?= __('warranty_expiry') ?></label><input type="date" name="warranty_expiry" class="form-control" value="<?= e($asset['warranty_expiry'] ?? '') ?>"></div>
            <div class="col-md-4"><label class="form-label"><?= __('cost') ?></label><input type="number" step="0.01" name="cost" class="form-control" value="<?= e((string) $asset['cost']) ?>"></div>
            <div class="col-md-4"><label class="form-label"><?= __('supplier') ?></label><input type="text" name="supplier" class="form-control" value="<?= e($asset['supplier'] ?? '') ?>"></div>
            <div class="col-md-4"><label class="form-label"><?= __('location') ?></label><input type="text" name="location" class="form-control" value="<?= e($asset['location'] ?? '') ?>"></div>
            <div class="col-md-4"><label class="form-label"><?= __('department') ?></label><select name="department_id" class="form-select"><option value=""><?= __('select') ?></option><?php foreach ($departments as $d): ?><option value="<?= (int) $d['id'] ?>"<?= (int) $d['id'] === (int) $asset['department_id'] ? ' selected' : '' ?>><?= e($d['name']) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label class="form-label"><?= __('status') ?></label><select name="status" class="form-select"><?php foreach (['active','assigned','maintenance','retired','lost'] as $s): ?><option value="<?= $s ?>"<?= $asset['status'] === $s ? ' selected' : '' ?>><?= __($s) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-6"><label class="form-label"><?= __('description') ?></label><textarea name="description" class="form-control" rows="2"><?= e($asset['description'] ?? '') ?></textarea></div>
            <div class="col-md-6"><label class="form-label"><?= __('notes') ?> (Document)</label><input type="file" name="document" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"><?php if ($asset['document_path']): ?><small class="text-muted"><?= __('current') ?>: <?= e(basename($asset['document_path'])) ?></small><?php endif; ?></div>
        </div>
        <div class="mt-3"><button type="submit" class="btn btn-brand"><?= __('update') ?></button><a href="<?= baseUrl('modules/assets/index.php') ?>" class="btn btn-secondary"><?= __('cancel') ?></a></div>
    </form>
</div></div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
