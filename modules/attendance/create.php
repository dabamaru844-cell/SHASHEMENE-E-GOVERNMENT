<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole('admin', 'hr');

$employees = getEmployeesList($pdo);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $error = __('csrf_invalid');
    } else {
        $employeeId = (int) ($_POST['employee_id'] ?? 0);
        $date = $_POST['attendance_date'] ?? date('Y-m-d');
        $checkIn = $_POST['check_in'] ?: null;
        $checkOut = $_POST['check_out'] ?: null;
        $status = $_POST['status'] ?? 'present';
        $hours = calculateWorkingHours($checkIn, $checkOut);
        try {
            $pdo->prepare(
                'INSERT INTO attendance (employee_id, attendance_date, check_in, check_out, working_hours, status, recorded_by)
                 VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE check_in=VALUES(check_in), check_out=VALUES(check_out), working_hours=VALUES(working_hours), status=VALUES(status), recorded_by=VALUES(recorded_by)'
            )->execute([$employeeId, $date, $checkIn, $checkOut, $hours, $status, (int) currentUser()['id']]);
            logActivity($pdo, (int) currentUser()['id'], 'create', 'attendance', $employeeId);
            flash('success', __('saved_success'));
            redirect(baseUrl('modules/attendance/index.php?date=' . $date));
        } catch (PDOException) {
            $error = __('error_occurred');
        }
    }
}

$pageTitle = __('mark_attendance');
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-header"><h1><?= __('mark_attendance') ?></h1></div>
<div class="row justify-content-center"><div class="col-md-6">
<div class="card"><div class="card-body">
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <form method="post"><?= csrfField() ?>
        <div class="mb-3"><label class="form-label"><?= __('employees') ?></label>
            <select name="employee_id" class="form-select" required><option value=""><?= __('select') ?></option>
                <?php foreach ($employees as $e): ?><option value="<?= (int) $e['id'] ?>"><?= e($e['employee_code'] . ' - ' . $e['first_name'] . ' ' . $e['last_name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3"><label class="form-label"><?= __('date') ?></label><input type="date" name="attendance_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
        <div class="row g-3 mb-3">
            <div class="col-md-6"><label class="form-label"><?= __('check_in') ?></label><input type="time" name="check_in" class="form-control"></div>
            <div class="col-md-6"><label class="form-label"><?= __('check_out') ?></label><input type="time" name="check_out" class="form-control"></div>
        </div>
        <div class="mb-3"><label class="form-label"><?= __('status') ?></label>
            <select name="status" class="form-select"><?php foreach (['present','absent','late','half_day','on_leave'] as $s): ?><option value="<?= $s ?>"><?= __($s) ?></option><?php endforeach; ?></select>
        </div>
        <button type="submit" class="btn btn-brand"><?= __('save') ?></button>
        <a href="<?= baseUrl('modules/attendance/index.php') ?>" class="btn btn-secondary"><?= __('cancel') ?></a>
    </form>
</div></div></div></div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
