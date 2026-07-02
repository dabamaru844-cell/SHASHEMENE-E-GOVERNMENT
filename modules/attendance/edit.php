<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole('admin', 'hr');

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM attendance WHERE id = ?');
$stmt->execute([$id]);
$record = $stmt->fetch();
if (!$record) redirect(baseUrl('modules/attendance/index.php'));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $checkIn = $_POST['check_in'] ?: null;
    $checkOut = $_POST['check_out'] ?: null;
    $hours = calculateWorkingHours($checkIn, $checkOut);
    $pdo->prepare('UPDATE attendance SET check_in=?, check_out=?, working_hours=?, status=? WHERE id=?')
        ->execute([$checkIn, $checkOut, $hours, $_POST['status'] ?? 'present', $id]);
    logActivity($pdo, (int) currentUser()['id'], 'update', 'attendance', $id);
    flash('success', __('updated_success'));
    redirect(baseUrl('modules/attendance/index.php?date=' . $record['attendance_date']));
}

$pageTitle = __('edit') . ' ' . __('attendance');
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-header"><h1><?= __('edit') ?> <?= __('attendance') ?></h1></div>
<div class="row justify-content-center"><div class="col-md-6">
<div class="card"><div class="card-body">
    <form method="post"><?= csrfField() ?>
        <div class="row g-3 mb-3">
            <div class="col-md-6"><label class="form-label"><?= __('check_in') ?></label><input type="time" name="check_in" class="form-control" value="<?= e($record['check_in'] ?? '') ?>"></div>
            <div class="col-md-6"><label class="form-label"><?= __('check_out') ?></label><input type="time" name="check_out" class="form-control" value="<?= e($record['check_out'] ?? '') ?>"></div>
        </div>
        <div class="mb-3"><label class="form-label"><?= __('status') ?></label>
            <select name="status" class="form-select"><?php foreach (['present','absent','late','half_day','on_leave'] as $s): ?><option value="<?= $s ?>"<?= $record['status'] === $s ? ' selected' : '' ?>><?= __($s) ?></option><?php endforeach; ?></select>
        </div>
        <button type="submit" class="btn btn-brand"><?= __('update') ?></button>
        <a href="<?= baseUrl('modules/attendance/index.php') ?>" class="btn btn-secondary"><?= __('cancel') ?></a>
    </form>
</div></div></div></div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
