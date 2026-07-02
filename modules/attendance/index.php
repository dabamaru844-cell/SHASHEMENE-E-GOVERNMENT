<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
requireModule('attendance');

$user = currentUser();
$page = max(1, (int) ($_GET['page'] ?? 1));
$date = $_GET['date'] ?? date('Y-m-d');
$status = $_GET['status'] ?? '';

$where = ['a.attendance_date = ?'];
$params = [$date];
if ($status !== '' && in_array($status, ['present','absent','late','half_day','on_leave'], true)) {
    $where[] = 'a.status = ?';
    $params[] = $status;
}
if (hasRole('employee') && !empty($user['employee_id'])) {
    $where[] = 'a.employee_id = ?';
    $params[] = (int) $user['employee_id'];
}
$whereSql = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM attendance a WHERE $whereSql");
$countStmt->execute($params);
$pagination = paginate((int) $countStmt->fetchColumn(), $page);

$stmt = $pdo->prepare(
    "SELECT a.*, e.employee_code, e.first_name, e.last_name FROM attendance a
     JOIN employees e ON a.employee_id = e.id
     WHERE $whereSql ORDER BY e.last_name LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}"
);
$stmt->execute($params);
$records = $stmt->fetchAll();

$pageTitle = __('attendance');
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h1><?= __('attendance') ?></h1>
    <?php if (hasRole('admin', 'hr')): ?>
    <a href="<?= baseUrl('modules/attendance/create.php') ?>" class="btn btn-brand"><?= __('mark_attendance') ?></a>
    <?php endif; ?>
</div>
<div class="card"><div class="card-body">
    <form method="get" class="row g-2 mb-3">
        <div class="col-md-3"><input type="date" name="date" class="form-control" value="<?= e($date) ?>"></div>
        <div class="col-md-2"><select name="status" class="form-select"><option value=""><?= __('all') ?></option>
            <?php foreach (['present','absent','late','half_day','on_leave'] as $s): ?><option value="<?= $s ?>"<?= $status === $s ? ' selected' : '' ?>><?= __($s) ?></option><?php endforeach; ?>
        </select></div>
        <div class="col-auto"><button type="submit" class="btn btn-primary"><?= __('filter') ?></button></div>
    </form>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead><tr>
                <th><?= __('employee_id') ?></th><th><?= __('first_name') ?></th><th><?= __('date') ?></th>
                <th><?= __('check_in') ?></th><th><?= __('check_out') ?></th><th><?= __('working_hours') ?></th><th><?= __('status') ?></th>
                <?php if (hasRole('admin', 'hr')): ?><th><?= __('actions') ?></th><?php endif; ?>
            </tr></thead>
            <tbody>
            <?php foreach ($records as $r): ?>
            <tr>
                <td><?= e($r['employee_code']) ?></td>
                <td><?= e($r['first_name'] . ' ' . $r['last_name']) ?></td>
                <td><?= formatDate($r['attendance_date']) ?></td>
                <td><?= formatTime($r['check_in']) ?></td>
                <td><?= formatTime($r['check_out']) ?></td>
                <td><?= e((string) $r['working_hours']) ?></td>
                <td><span class="badge bg-<?= match($r['status']) { 'present'=>'success','absent'=>'danger','late'=>'warning', default=>'secondary' } ?>"><?= e(__($r['status'])) ?></span></td>
                <?php if (hasRole('admin', 'hr')): ?>
                <td><a href="<?= baseUrl('modules/attendance/edit.php?id=' . $r['id']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a></td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($records)): ?><tr><td colspan="8" class="text-center text-muted"><?= __('no_records') ?></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php $q = http_build_query(array_filter(['date'=>$date,'status'=>$status?:null]));
    echo renderPagination($pagination, baseUrl('modules/attendance/index.php') . ($q ? '?' . $q : '')); ?>
</div></div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
