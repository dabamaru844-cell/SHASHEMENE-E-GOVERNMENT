<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
requireModule('assets');

$page = max(1, (int) ($_GET['page'] ?? 1));
$search = trim($_GET['search'] ?? '');
$category = (int) ($_GET['category'] ?? 0);
$status = $_GET['status'] ?? '';
$categories = getAssetCategories($pdo);

$where = ['1=1'];
$params = [];
if ($search !== '') {
    $where[] = '(a.asset_code LIKE ? OR a.name LIKE ? OR a.serial_number LIKE ?)';
    array_push($params, "%$search%", "%$search%", "%$search%");
}
if ($category > 0) {
    $where[] = 'a.category_id = ?';
    $params[] = $category;
}
if ($status !== '' && in_array($status, ['active','assigned','maintenance','retired','lost'], true)) {
    $where[] = 'a.status = ?';
    $params[] = $status;
}
$whereSql = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM assets a WHERE $whereSql");
$countStmt->execute($params);
$pagination = paginate((int) $countStmt->fetchColumn(), $page);

$stmt = $pdo->prepare(
    "SELECT a.*, c.name AS category_name, d.name AS department_name FROM assets a
     JOIN asset_categories c ON a.category_id = c.id
     LEFT JOIN departments d ON a.department_id = d.id
     WHERE $whereSql ORDER BY a.created_at DESC LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}"
);
$stmt->execute($params);
$assets = $stmt->fetchAll();

$pageTitle = __('assets');
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h1><?= __('assets') ?></h1>
    <?php if (hasRole('admin', 'it')): ?>
    <div class="d-flex gap-2">
        <a href="<?= baseUrl('modules/assets/assign.php') ?>" class="btn btn-outline-primary"><?= __('assign_asset') ?></a>
        <a href="<?= baseUrl('modules/assets/create.php') ?>" class="btn btn-brand"><?= __('add_asset') ?></a>
    </div>
    <?php endif; ?>
</div>
<div class="card"><div class="card-body">
    <form method="get" class="row g-2 mb-3">
        <div class="col-md-3"><input type="text" name="search" class="form-control" placeholder="<?= __('search') ?>" value="<?= e($search) ?>"></div>
        <div class="col-md-2"><select name="category" class="form-select"><option value=""><?= __('all') ?> <?= __('category') ?></option>
            <?php foreach ($categories as $c): ?><option value="<?= (int) $c['id'] ?>"<?= $category === (int) $c['id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
        </select></div>
        <div class="col-md-2"><select name="status" class="form-select"><option value=""><?= __('all') ?> <?= __('status') ?></option>
            <?php foreach (['active','assigned','maintenance','retired','lost'] as $s): ?><option value="<?= $s ?>"<?= $status === $s ? ' selected' : '' ?>><?= __($s) ?></option><?php endforeach; ?>
        </select></div>
        <div class="col-auto"><button type="submit" class="btn btn-primary"><?= __('filter') ?></button></div>
    </form>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead><tr>
                <th><?= __('asset_id') ?></th><th><?= __('asset_name') ?></th><th><?= __('category') ?></th>
                <th><?= __('brand') ?></th><th><?= __('status') ?></th><th><?= __('location') ?></th><th><?= __('actions') ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($assets as $a): ?>
            <tr>
                <td><?= e($a['asset_code']) ?></td>
                <td><?= e($a['name']) ?></td>
                <td><?= e($a['category_name']) ?></td>
                <td><?= e($a['brand'] ?: '-') ?></td>
                <td><span class="badge bg-<?= match($a['status']) { 'active'=>'success','assigned'=>'primary','maintenance'=>'warning','retired'=>'secondary','lost'=>'danger', default=>'secondary' } ?>"><?= e(__($a['status'])) ?></span></td>
                <td><?= e($a['location'] ?: '-') ?></td>
                <td>
                    <a href="<?= baseUrl('modules/assets/view.php?id=' . $a['id']) ?>" class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i></a>
                    <?php if (hasRole('admin', 'it')): ?>
                    <a href="<?= baseUrl('modules/assets/edit.php?id=' . $a['id']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                    <a href="<?= baseUrl('modules/assets/delete.php?id=' . $a['id']) ?>" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($assets)): ?><tr><td colspan="7" class="text-center text-muted"><?= __('no_records') ?></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php $q = http_build_query(array_filter(['search'=>$search,'category'=>$category?:null,'status'=>$status?:null]));
    echo renderPagination($pagination, baseUrl('modules/assets/index.php') . ($q ? '?' . $q : '')); ?>
</div></div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
