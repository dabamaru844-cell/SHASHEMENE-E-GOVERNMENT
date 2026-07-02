<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
requireModule('leave');

$user = currentUser();
$error = '';
$employees = hasRole('admin', 'hr') ? getEmployeesList($pdo) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $error = __('csrf_invalid');
    } else {
        $employeeId = hasRole('admin', 'hr') ? (int) ($_POST['employee_id'] ?? 0) : (int) ($user['employee_id'] ?? 0);
        if ($employeeId) {
            $pdo->prepare(
                'INSERT INTO leave_requests (employee_id, leave_type, start_date, end_date, reason) VALUES (?,?,?,?,?)'
            )->execute([
                $employeeId,
                $_POST['leave_type'] ?? 'annual_leave',
                $_POST['start_date'] ?? '',
                $_POST['end_date'] ?? '',
                trim($_POST['reason'] ?? ''),
            ]);
            notifyRole($pdo, 2, 'leave', __('leave'), __('request_leave'), baseUrl('modules/leave/index.php'));
            flash('success', __('saved_success'));
            redirect(baseUrl('modules/leave/index.php'));
        } else {
            $error = __('error_occurred');
        }
    }
}

$pageTitle = __('request_leave');
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-header"><h1><?= __('request_leave') ?></h1></div>
<div class="row justify-content-center"><div class="col-md-6">
<div class="card"><div class="card-body">
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <form method="post"><?= csrfField() ?>
        <?php if (hasRole('admin', 'hr')): ?>
        <div class="mb-3"><label class="form-label"><?= __('employees') ?></label>
            <select name="employee_id" class="form-select" required><option value=""><?= __('select') ?></option>
                <?php foreach ($employees as $e): ?><option value="<?= (int) $e['id'] ?>"><?= e($e['employee_code'] . ' - ' . $e['first_name'] . ' ' . $e['last_name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <div class="mb-3"><label class="form-label"><?= __('leave_type') ?></label>
            <select name="leave_type" class="form-select">
                <?php foreach (['annual_leave','sick_leave','maternity_leave','emergency_leave'] as $t): ?>
                <option value="<?= $t ?>"><?= __($t) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="row g-3 mb-3">
            <div class="col-md-6"><label class="form-label"><?= __('start_date') ?></label><input type="date" name="start_date" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label"><?= __('end_date') ?></label><input type="date" name="end_date" class="form-control" required></div>
        </div>
        <div class="mb-3"><label class="form-label"><?= __('reason') ?></label><textarea name="reason" class="form-control" rows="3"></textarea></div>
        <button type="submit" class="btn btn-brand"><?= __('submit') ?></button>
        <a href="<?= baseUrl('modules/leave/index.php') ?>" class="btn btn-secondary"><?= __('cancel') ?></a>
    </form>
</div></div></div></div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
