<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
requireModule('leave');

$user = currentUser();
$page = max(1, (int) ($_GET['page'] ?? 1));

$where = ['1=1'];
$params = [];
if (hasRole('employee') && !empty($user['employee_id'])) {
    $where[] = 'lr.employee_id = ?';
    $params[] = (int) $user['employee_id'];
}
$whereSql = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM leave_requests lr WHERE $whereSql");
$countStmt->execute($params);
$pagination = paginate((int) $countStmt->fetchColumn(), $page);

$stmt = $pdo->prepare(
    "SELECT lr.*, e.employee_code, e.first_name, e.last_name FROM leave_requests lr
     JOIN employees e ON lr.employee_id = e.id WHERE $whereSql ORDER BY lr.created_at DESC
     LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}"
);
$stmt->execute($params);
$leaves = $stmt->fetchAll();

$pageTitle = __('leave');
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h1><?= __('leave') ?></h1>
    <a href="<?= baseUrl('modules/leave/create.php') ?>" class="btn btn-brand"><?= __('request_leave') ?></a>
</div>
<div class="card"><div class="card-body">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead><tr>
                <th><?= __('employee_id') ?></th><th><?= __('leave_type') ?></th><th><?= __('start_date') ?></th>
                <th><?= __('end_date') ?></th><th><?= __('approval_status') ?></th>
                <?php if (hasRole('admin', 'hr')): ?><th><?= __('actions') ?></th><?php endif; ?>
            </tr></thead>
            <tbody>
            <?php foreach ($leaves as $l): ?>
            <tr>
                <td><?= e($l['employee_code'] . ' - ' . $l['first_name'] . ' ' . $l['last_name']) ?></td>
                <td><?= e($l['leave_type']) ?></td>
                <td><?= formatDate($l['start_date']) ?></td>
                <td><?= formatDate($l['end_date']) ?></td>
                <td><span class="badge bg-<?= match($l['approval_status']) { 'approved'=>'success','rejected'=>'danger', default=>'warning' } ?>"><?= e(__($l['approval_status'])) ?></span></td>
                <?php if (hasRole('admin', 'hr') && $l['approval_status'] === 'pending'): ?>
                <td>
                    <a href="<?= baseUrl('modules/leave/approve.php?id=' . $l['id'] . '&action=approved') ?>" class="btn btn-sm btn-success"><?= __('approve') ?></a>
                    <a href="<?= baseUrl('modules/leave/approve.php?id=' . $l['id'] . '&action=rejected') ?>" class="btn btn-sm btn-danger"><?= __('reject') ?></a>
                </td>
                <?php elseif (hasRole('admin', 'hr')): ?><td>-</td><?php endif; ?>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($leaves)): ?><tr><td colspan="6" class="text-center text-muted"><?= __('no_records') ?></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?= renderPagination($pagination, baseUrl('modules/leave/index.php')) ?>
</div></div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
