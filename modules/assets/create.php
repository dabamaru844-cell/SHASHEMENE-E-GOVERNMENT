<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole('admin', 'it');

$categories = getAssetCategories($pdo);
$departments = getDepartments($pdo);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $error = __('csrf_invalid');
    } else {
        $doc = null;
        if (!empty($_FILES['document']['name'])) {
            $doc = uploadFile($_FILES['document'], 'documents', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']);
        }
        $code = generateAssetCode($pdo);
        try {
            $pdo->prepare(
                'INSERT INTO assets (asset_code, name, category_id, brand, model, serial_number, purchase_date, warranty_expiry, supplier, cost, status, location, description, document_path, department_id)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
            )->execute([
                $code, trim($_POST['name'] ?? ''), (int) ($_POST['category_id'] ?? 0),
                trim($_POST['brand'] ?? ''), trim($_POST['model'] ?? ''), trim($_POST['serial_number'] ?? ''),
                $_POST['purchase_date'] ?: null, $_POST['warranty_expiry'] ?: null,
                trim($_POST['supplier'] ?? ''), (float) ($_POST['cost'] ?? 0),
                $_POST['status'] ?? 'active', trim($_POST['location'] ?? ''),
                trim($_POST['description'] ?? ''), $doc,
                !empty($_POST['department_id']) ? (int) $_POST['department_id'] : null,
            ]);
            $id = (int) $pdo->lastInsertId();
            logActivity($pdo, (int) currentUser()['id'], 'create', 'assets', $id);
            flash('success', __('saved_success'));
            redirect(baseUrl('modules/assets/index.php'));
        } catch (PDOException) {
            $error = __('error_occurred');
        }
    }
}

$pageTitle = __('add_asset');
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-header"><h1><?= __('add_asset') ?></h1></div>
<div class="card"><div class="card-body">
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data"><?= csrfField() ?>
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label"><?= __('asset_name') ?></label><input type="text" name="name" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label"><?= __('category') ?></label><select name="category_id" class="form-select" required><?php foreach ($categories as $c): ?><option value="<?= (int) $c['id'] ?>"><?= e($c['parent_name'] ? $c['parent_name'] . ' > ' . $c['name'] : $c['name']) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label class="form-label"><?= __('brand') ?></label><input type="text" name="brand" class="form-control"></div>
            <div class="col-md-4"><label class="form-label"><?= __('model') ?></label><input type="text" name="model" class="form-control"></div>
            <div class="col-md-4"><label class="form-label"><?= __('serial_number') ?></label><input type="text" name="serial_number" class="form-control"></div>
            <div class="col-md-4"><label class="form-label"><?= __('purchase_date') ?></label><input type="date" name="purchase_date" class="form-control"></div>
            <div class="col-md-4"><label class="form-label"><?= __('warranty_expiry') ?></label><input type="date" name="warranty_expiry" class="form-control"></div>
            <div class="col-md-4"><label class="form-label"><?= __('cost') ?></label><input type="number" step="0.01" name="cost" class="form-control"></div>
            <div class="col-md-4"><label class="form-label"><?= __('supplier') ?></label><input type="text" name="supplier" class="form-control"></div>
            <div class="col-md-4"><label class="form-label"><?= __('location') ?></label><input type="text" name="location" class="form-control"></div>
            <div class="col-md-4"><label class="form-label"><?= __('department') ?></label><select name="department_id" class="form-select"><option value=""><?= __('select') ?></option><?php foreach ($departments as $d): ?><option value="<?= (int) $d['id'] ?>"><?= e($d['name']) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label class="form-label"><?= __('status') ?></label><select name="status" class="form-select"><?php foreach (['active','maintenance','retired'] as $s): ?><option value="<?= $s ?>"><?= __($s) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-6"><label class="form-label"><?= __('description') ?></label><textarea name="description" class="form-control" rows="2"></textarea></div>
            <div class="col-md-6"><label class="form-label"><?= __('notes') ?> (Document)</label><input type="file" name="document" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"></div>
        </div>
        <div class="mt-3"><button type="submit" class="btn btn-brand"><?= __('save') ?></button><a href="<?= baseUrl('modules/assets/index.php') ?>" class="btn btn-secondary"><?= __('cancel') ?></a></div>
    </form>
</div></div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
