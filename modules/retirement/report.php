<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole('admin', 'hr');

$status = $_GET['status'] ?? '';
$export = $_GET['export'] ?? '';

$where = ['r.status != "active"'];
$params = [];

if ($status && in_array($status, ['near_retirement', 'retirement_eligible', 'retired'], true)) {
    $where[] = 'r.status = ?';
    $params[] = $status;
}

$whereSql = implode(' AND ', $where);

$stmt = $pdo->prepare("
    SELECT e.employee_code, e.first_name, e.last_name, e.position, e.date_of_birth,
           d.name AS department_name, r.retirement_date, r.status,
           TIMESTAMPDIFF(YEAR, e.date_of_birth, CURDATE()) AS current_age
    FROM retirements r
    JOIN employees e ON r.employee_id = e.id
    JOIN departments d ON e.department_id = d.id
    WHERE $whereSql
    ORDER BY r.retirement_date ASC
");
$stmt->execute($params);
$retirements = $stmt->fetchAll();

if ($export === 'csv') {
    $headers = ['Employee ID', 'Name', 'Department', 'Position', 'Date of Birth', 'Current Age', 'Retirement Date', 'Status'];
    $rows = [];
    foreach ($retirements as $r) {
        $rows[] = [
            $r['employee_code'],
            $r['first_name'] . ' ' . $r['last_name'],
            $r['department_name'],
            $r['position'],
            $r['date_of_birth'],
            $r['current_age'] . ' years',
            $r['retirement_date'],
            __(''$r['status'])
        ];
    }
    exportCsv('retirement_report_' . date('Y-m-d') . '.csv', $headers, $rows);
}

$pageTitle = __('retirement_report');
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="bi bi-file-earmark-text me-2"></i><?= __('retirement_report') ?></h1>
        <div class="btn-group">
            <a href="<?= baseUrl('modules/retirement/report.php?export=csv' . ($status ? '&status=' . $status : '')) ?>" 
               class="btn btn-success">
                <i class="bi bi-file-earmark-excel me-1"></i><?= __('export_csv') ?>
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="bi bi-printer me-1"></i><?= __('print_report') ?>
            </button>
            <a href="<?= baseUrl('modules/retirement/index.php') ?>" class="btn btn-secondary">
                <?= __('back') ?>
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="mb-3">
            <form method="get" class="row g-2">
                <div class="col-auto">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value=""><?= __('all_status') ?></option>
                        <option value="near_retirement"<?= $status === 'near_retirement' ? ' selected' : '' ?>>
                            <?= __('near_retirement') ?>
                        </option>
                        <option value="retirement_eligible"<?= $status === 'retirement_eligible' ? ' selected' : '' ?>>
                            <?= __('retirement_eligible') ?>
                        </option>
                        <option value="retired"<?= $status === 'retired' ? ' selected' : '' ?>>
                            <?= __('retired') ?>
                        </option>
                    </select>
                </div>
            </form>
        </div>
        
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th><?= __('employee_id') ?></th>
                        <th><?= __('employee_name') ?></th>
                        <th><?= __('department') ?></th>
                        <th><?= __('position') ?></th>
                        <th><?= __('date_of_birth') ?></th>
                        <th><?= __('current_age') ?></th>
                        <th><?= __('retirement_date') ?></th>
                        <th><?= __('status') ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($retirements as $r): ?>
                    <tr>
                        <td><?= e($r['employee_code']) ?></td>
                        <td><?= e($r['first_name'] . ' ' . $r['last_name']) ?></td>
                        <td><?= e($r['department_name']) ?></td>
                        <td><?= e($r['position']) ?></td>
                        <td><?= formatDate($r['date_of_birth']) ?></td>
                        <td><?= (int) $r['current_age'] ?> <?= __('years') ?></td>
                        <td><?= formatDate($r['retirement_date']) ?></td>
                        <td><?= e(__($r['status'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($retirements)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted"><?= __('no_records') ?></td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="mt-3">
            <small class="text-muted">
                <?= __('generated_on') ?>: <?= date('F j, Y g:i A') ?><br>
                <?= __('total_records') ?>: <?= count($retirements) ?>
            </small>
        </div>
    </div>
</div>

<style media="print">
    .page-header .btn-group, .navbar, .sidebar, .card .mb-3 { display: none !important; }
</style>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
