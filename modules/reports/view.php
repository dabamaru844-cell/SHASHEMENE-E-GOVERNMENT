<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
requireModule('reports');

$report = $_GET['report'] ?? '';
$print = isset($_GET['print']);
$date = $_GET['date'] ?? date('Y-m-d');

$data = [];
$title = __('reports');

switch ($report) {
    case 'assets':
        $title = __('asset_inventory_report');
        $data = $pdo->query('SELECT a.*, c.name AS category_name FROM assets a JOIN asset_categories c ON a.category_id = c.id ORDER BY a.asset_code')->fetchAll();
        break;
    case 'assigned_assets':
        $title = __('assigned_asset_report');
        $data = $pdo->query('SELECT a.asset_code, a.name, e.employee_code, e.first_name, e.last_name, aa.assignment_date FROM asset_assignments aa JOIN assets a ON aa.asset_id = a.id JOIN employees e ON aa.employee_id = e.id WHERE aa.status = "active"')->fetchAll();
        break;
    case 'employees':
        $title = __('employee_list_report');
        $data = $pdo->query('SELECT e.*, d.name AS department_name FROM employees e JOIN departments d ON e.department_id = d.id ORDER BY e.last_name')->fetchAll();
        break;
    case 'attendance':
        $title = __('daily_attendance_report') . ' - ' . formatDate($date);
        $stmt = $pdo->prepare('SELECT a.*, e.employee_code, e.first_name, e.last_name FROM attendance a JOIN employees e ON a.employee_id = e.id WHERE a.attendance_date = ? ORDER BY e.last_name');
        $stmt->execute([$date]);
        $data = $stmt->fetchAll();
        break;
    case 'leave':
        $title = __('leave_report');
        $data = $pdo->query('SELECT lr.*, e.employee_code, e.first_name, e.last_name FROM leave_requests lr JOIN employees e ON lr.employee_id = e.id ORDER BY lr.start_date DESC')->fetchAll();
        break;
    default:
        redirect(baseUrl('modules/reports/index.php'));
}

$pageTitle = $title;
if ($print) {
    ?><!DOCTYPE html><html><head><meta charset="UTF-8"><title><?= e($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body{padding:2rem} @media print{.no-print{display:none}}</style></head><body>
    <div class="text-center mb-4"><img src="<?= assetUrl('img/logo.png') ?>" width="60" alt=""><h2><?= e(__('app_name')) ?></h2><h4><?= e($title) ?></h4><p><?= date('d M Y H:i') ?></p></div>
    <?php
} else {
    require __DIR__ . '/../../includes/header.php';
    echo '<div class="page-header d-flex justify-content-between no-print"><h1>' . e($title) . '</h1>';
    echo '<button onclick="window.print()" class="btn btn-brand">' . __('print_report') . '</button></div>';
}
?>
<div class="card"><div class="card-body p-0">
<div class="table-responsive">
<table class="table table-bordered mb-0">
<thead class="table-light"><tr>
<?php if ($report === 'assets'): ?>
<th><?= __('asset_id') ?></th><th><?= __('asset_name') ?></th><th><?= __('category') ?></th><th><?= __('brand') ?></th><th><?= __('status') ?></th><th><?= __('cost') ?></th>
<?php elseif ($report === 'assigned_assets'): ?>
<th><?= __('asset_id') ?></th><th><?= __('asset_name') ?></th><th><?= __('employee_id') ?></th><th><?= __('first_name') ?></th><th><?= __('assignment_date') ?></th>
<?php elseif ($report === 'employees'): ?>
<th><?= __('employee_id') ?></th><th><?= __('first_name') ?></th><th><?= __('last_name') ?></th><th><?= __('department') ?></th><th><?= __('position') ?></th><th><?= __('status') ?></th>
<?php elseif ($report === 'attendance'): ?>
<th><?= __('employee_id') ?></th><th><?= __('first_name') ?></th><th><?= __('check_in') ?></th><th><?= __('check_out') ?></th><th><?= __('working_hours') ?></th><th><?= __('status') ?></th>
<?php elseif ($report === 'leave'): ?>
<th><?= __('employee_id') ?></th><th><?= __('leave_type') ?></th><th><?= __('start_date') ?></th><th><?= __('end_date') ?></th><th><?= __('approval_status') ?></th>
<?php endif; ?>
</tr></thead><tbody>
<?php foreach ($data as $row): ?>
<tr>
<?php if ($report === 'assets'): ?>
<td><?= e($row['asset_code']) ?></td><td><?= e($row['name']) ?></td><td><?= e($row['category_name']) ?></td><td><?= e($row['brand'] ?? '-') ?></td><td><?= e(__($row['status'])) ?></td><td><?= number_format((float) $row['cost'], 2) ?></td>
<?php elseif ($report === 'assigned_assets'): ?>
<td><?= e($row['asset_code']) ?></td><td><?= e($row['name']) ?></td><td><?= e($row['employee_code']) ?></td><td><?= e($row['first_name'] . ' ' . $row['last_name']) ?></td><td><?= formatDate($row['assignment_date']) ?></td>
<?php elseif ($report === 'employees'): ?>
<td><?= e($row['employee_code']) ?></td><td><?= e($row['first_name']) ?></td><td><?= e($row['last_name']) ?></td><td><?= e($row['department_name']) ?></td><td><?= e($row['position']) ?></td><td><?= e(__($row['employment_status'])) ?></td>
<?php elseif ($report === 'attendance'): ?>
<td><?= e($row['employee_code']) ?></td><td><?= e($row['first_name'] . ' ' . $row['last_name']) ?></td><td><?= formatTime($row['check_in']) ?></td><td><?= formatTime($row['check_out']) ?></td><td><?= e((string) $row['working_hours']) ?></td><td><?= e(__($row['status'])) ?></td>
<?php elseif ($report === 'leave'): ?>
<td><?= e($row['employee_code'] . ' - ' . $row['first_name']) ?></td><td><?= e($row['leave_type']) ?></td><td><?= formatDate($row['start_date']) ?></td><td><?= formatDate($row['end_date']) ?></td><td><?= e(__($row['approval_status'])) ?></td>
<?php endif; ?>
</tr>
<?php endforeach; ?>
<?php if (empty($data)): ?><tr><td colspan="6" class="text-center text-muted"><?= __('no_records') ?></td></tr><?php endif; ?>
</tbody></table></div></div></div>
<?php if ($print): ?></body></html><?php else: ?>
<a href="<?= baseUrl('modules/reports/index.php') ?>" class="btn btn-secondary mt-3 no-print"><?= __('back') ?></a>
<?php require __DIR__ . '/../../includes/footer.php'; endif; ?>
