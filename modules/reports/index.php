<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
requireModule('reports');

$report = $_GET['report'] ?? '';
$export = $_GET['export'] ?? '';

if ($report && $export === 'csv') {
    switch ($report) {
        case 'assets':
            $rows = $pdo->query('SELECT asset_code, name, brand, model, serial_number, status, location, cost FROM assets ORDER BY asset_code')->fetchAll(PDO::FETCH_NUM);
            exportCsv('asset_inventory.csv', [__('asset_id'), __('asset_name'), __('brand'), __('model'), __('serial_number'), __('status'), __('location'), __('cost')], $rows);
        case 'employees':
            $rows = $pdo->query('SELECT e.employee_code, e.first_name, e.last_name, d.name, e.position, e.phone, e.email, e.employment_status FROM employees e JOIN departments d ON e.department_id = d.id ORDER BY e.last_name')->fetchAll(PDO::FETCH_NUM);
            exportCsv('employees.csv', [__('employee_id'), __('first_name'), __('last_name'), __('department'), __('position'), __('phone'), __('email'), __('status')], $rows);
        case 'attendance':
            $date = $_GET['date'] ?? date('Y-m-d');
            $stmt = $pdo->prepare('SELECT e.employee_code, e.first_name, e.last_name, a.attendance_date, a.check_in, a.check_out, a.working_hours, a.status FROM attendance a JOIN employees e ON a.employee_id = e.id WHERE a.attendance_date = ?');
            $stmt->execute([$date]);
            exportCsv('attendance_' . $date . '.csv', [__('employee_id'), __('first_name'), __('last_name'), __('date'), __('check_in'), __('check_out'), __('working_hours'), __('status')], $stmt->fetchAll(PDO::FETCH_NUM));
        case 'leave':
            $rows = $pdo->query('SELECT e.employee_code, e.first_name, e.last_name, lr.leave_type, lr.start_date, lr.end_date, lr.approval_status FROM leave_requests lr JOIN employees e ON lr.employee_id = e.id ORDER BY lr.start_date DESC')->fetchAll(PDO::FETCH_NUM);
            exportCsv('leave_report.csv', [__('employee_id'), __('first_name'), __('last_name'), __('leave_type'), __('start_date'), __('end_date'), __('approval_status')], $rows);
        case 'assigned_assets':
            $rows = $pdo->query('SELECT a.asset_code, a.name, e.employee_code, e.first_name, e.last_name, aa.assignment_date FROM asset_assignments aa JOIN assets a ON aa.asset_id = a.id JOIN employees e ON aa.employee_id = e.id WHERE aa.status = "active"')->fetchAll(PDO::FETCH_NUM);
            exportCsv('assigned_assets.csv', [__('asset_id'), __('asset_name'), __('employee_id'), __('first_name'), __('last_name'), __('assignment_date')], $rows);
    }
}

$pageTitle = __('reports');
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-header"><h1><?= __('reports') ?></h1></div>
<div class="row g-3">
    <?php
    $reports = [];
    if (canAccess('assets')) {
        $reports[] = ['key' => 'assets', 'title' => __('asset_inventory_report'), 'icon' => 'pc-display'];
        $reports[] = ['key' => 'assigned_assets', 'title' => __('assigned_asset_report'), 'icon' => 'link-45deg'];
    }
    if (canAccess('employees')) {
        $reports[] = ['key' => 'employees', 'title' => __('employee_list_report'), 'icon' => 'people'];
    }
    if (canAccess('attendance')) {
        $reports[] = ['key' => 'attendance', 'title' => __('daily_attendance_report'), 'icon' => 'calendar-check'];
        $reports[] = ['key' => 'leave', 'title' => __('leave_report'), 'icon' => 'calendar-x'];
    }
    foreach ($reports as $r):
    ?>
    <div class="col-md-6 col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="stat-icon primary"><i class="bi bi-<?= e($r['icon']) ?>"></i></div>
                    <h5 class="mb-0"><?= e($r['title']) ?></h5>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="<?= baseUrl('modules/reports/view.php?report=' . $r['key']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye me-1"></i><?= __('view') ?></a>
                    <a href="?report=<?= e($r['key']) ?>&export=csv<?= $r['key'] === 'attendance' ? '&date=' . date('Y-m-d') : '' ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-spreadsheet me-1"></i><?= __('export_excel') ?></a>
                    <a href="<?= baseUrl('modules/reports/view.php?report=' . $r['key'] . '&print=1') ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer me-1"></i><?= __('print_report') ?></a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
