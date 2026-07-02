<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole('admin', 'it');

$employees = getEmployeesList($pdo);
$assets = $pdo->query("SELECT id, asset_code, name FROM assets WHERE status = 'active' ORDER BY name")->fetchAll();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $error = __('csrf_invalid');
    } else {
        $employeeId = (int) ($_POST['employee_id'] ?? 0);
        $assetId = (int) ($_POST['asset_id'] ?? 0);
        $date = $_POST['assignment_date'] ?? date('Y-m-d');
        if ($employeeId && $assetId) {
            $pdo->beginTransaction();
            try {
                $pdo->prepare(
                    'INSERT INTO asset_assignments (employee_id, asset_id, assignment_date, assigned_by) VALUES (?,?,?,?)'
                )->execute([$employeeId, $assetId, $date, (int) currentUser()['id']]);
                $pdo->prepare("UPDATE assets SET status = 'assigned' WHERE id = ?")->execute([$assetId]);
                $pdo->commit();
                $emp = $pdo->prepare('SELECT employee_code, first_name, last_name FROM employees WHERE id = ?');
                $emp->execute([$employeeId]);
                $e = $emp->fetch();
                notifyRole($pdo, 3, 'asset_assigned', __('asset_assigned'), ($e['employee_code'] ?? '') . ' - ' . ($e['first_name'] ?? ''), baseUrl('modules/assets/view.php?id=' . $assetId));
                logActivity($pdo, (int) currentUser()['id'], 'assign', 'assets', $assetId);
                flash('success', __('saved_success'));
                redirect(baseUrl('modules/assets/index.php'));
            } catch (PDOException) {
                $pdo->rollBack();
                $error = __('error_occurred');
            }
        } else {
            $error = __('error_occurred');
        }
    }
}

$pageTitle = __('assign_asset');
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-header"><h1><?= __('assign_asset') ?></h1></div>
<div class="row justify-content-center"><div class="col-md-6">
<div class="card"><div class="card-body">
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <form method="post"><?= csrfField() ?>
        <div class="mb-3"><label class="form-label"><?= __('employees') ?></label>
            <select name="employee_id" class="form-select" required><option value=""><?= __('select') ?></option>
                <?php foreach ($employees as $e): ?><option value="<?= (int) $e['id'] ?>"><?= e($e['employee_code'] . ' - ' . $e['first_name'] . ' ' . $e['last_name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3"><label class="form-label"><?= __('assets') ?></label>
            <select name="asset_id" class="form-select" required><option value=""><?= __('select') ?></option>
                <?php foreach ($assets as $a): ?><option value="<?= (int) $a['id'] ?>"><?= e($a['asset_code'] . ' - ' . $a['name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3"><label class="form-label"><?= __('assignment_date') ?></label><input type="date" name="assignment_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
        <button type="submit" class="btn btn-brand"><?= __('assign_asset') ?></button>
        <a href="<?= baseUrl('modules/assets/index.php') ?>" class="btn btn-secondary"><?= __('cancel') ?></a>
    </form>
</div></div></div></div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
