<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole('admin', 'hr');

// Calculate retirement eligibility for all active employees
$updateStmt = $pdo->query("
    SELECT e.id, e.date_of_birth, 
           TIMESTAMPDIFF(YEAR, e.date_of_birth, CURDATE()) AS age,
           DATE_ADD(e.date_of_birth, INTERVAL 60 YEAR) AS retirement_date
    FROM employees e
    WHERE e.employment_status = 'active'
");

while ($emp = $updateStmt->fetch()) {
    $age = (int) $emp['age'];
    $retirementDate = $emp['retirement_date'];
    
    // Check if retirement record exists
    $checkStmt = $pdo->prepare('SELECT id, status, notified FROM retirements WHERE employee_id = ?');
    $checkStmt->execute([$emp['id']]);
    $retirement = $checkStmt->fetch();
    
    $newStatus = 'active';
    if ($age >= 60) {
        $newStatus = 'retirement_eligible';
    } elseif ($age >= 59) {
        $newStatus = 'near_retirement';
    }
    
    if (!$retirement) {
        // Insert new retirement record
        $pdo->prepare('INSERT INTO retirements (employee_id, retirement_date, status) VALUES (?, ?, ?)')
            ->execute([$emp['id'], $retirementDate, $newStatus]);
        
        // Create notification if retirement eligible
        if ($newStatus === 'retirement_eligible') {
            $empDetails = $pdo->prepare('SELECT first_name, last_name FROM employees WHERE id = ?');
            $empDetails->execute([$emp['id']]);
            $empData = $empDetails->fetch();
            $name = $empData['first_name'] . ' ' . $empData['last_name'];
            
            notifyRole($pdo, 1, 'retirement', 'Retirement Eligible', 
                "Employee $name has reached retirement age (60 years)", 
                baseUrl('modules/retirement/view.php?id=' . $emp['id']));
            notifyRole($pdo, 2, 'retirement', 'Retirement Eligible', 
                "Employee $name has reached retirement age (60 years)", 
                baseUrl('modules/retirement/view.php?id=' . $emp['id']));
        }
    } else {
        // Update existing record if status changed
        if ($retirement['status'] !== $newStatus) {
            $pdo->prepare('UPDATE retirements SET status = ?, notified = 0 WHERE employee_id = ?')
                ->execute([$newStatus, $emp['id']]);
            
            // Create notification if newly retirement eligible
            if ($newStatus === 'retirement_eligible' && $retirement['notified'] == 0) {
                $empDetails = $pdo->prepare('SELECT first_name, last_name FROM employees WHERE id = ?');
                $empDetails->execute([$emp['id']]);
                $empData = $empDetails->fetch();
                $name = $empData['first_name'] . ' ' . $empData['last_name'];
                
                notifyRole($pdo, 1, 'retirement', 'Retirement Eligible', 
                    "Employee $name has reached retirement age (60 years)", 
                    baseUrl('modules/retirement/view.php?id=' . $emp['id']));
                notifyRole($pdo, 2, 'retirement', 'Retirement Eligible', 
                    "Employee $name has reached retirement age (60 years)", 
                    baseUrl('modules/retirement/view.php?id=' . $emp['id']));
                
                $pdo->prepare('UPDATE retirements SET notified = 1 WHERE employee_id = ?')
                    ->execute([$emp['id']]);
            }
        }
    }
}

// Get statistics
$totalNearRetirement = $pdo->query("SELECT COUNT(*) FROM retirements WHERE status = 'near_retirement'")->fetchColumn();
$totalEligible = $pdo->query("SELECT COUNT(*) FROM retirements WHERE status = 'retirement_eligible'")->fetchColumn();
$totalRetired = $pdo->query("SELECT COUNT(*) FROM retirements WHERE status = 'retired'")->fetchColumn();
$retiringThisMonth = $pdo->query("
    SELECT COUNT(*) FROM retirements 
    WHERE MONTH(retirement_date) = MONTH(CURDATE()) 
    AND YEAR(retirement_date) = YEAR(CURDATE())
    AND status IN ('near_retirement', 'retirement_eligible')
")->fetchColumn();

// Get filtered list
$status = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));

$where = ['r.status != "active"'];
$params = [];

if ($status && in_array($status, ['near_retirement', 'retirement_eligible', 'retired'], true)) {
    $where[] = 'r.status = ?';
    $params[] = $status;
}

if ($search !== '') {
    $where[] = '(e.employee_code LIKE ? OR e.first_name LIKE ? OR e.last_name LIKE ?)';
    array_push($params, "%$search%", "%$search%", "%$search%");
}

$whereSql = implode(' AND ', $where);

$countStmt = $pdo->prepare("
    SELECT COUNT(*) FROM retirements r
    JOIN employees e ON r.employee_id = e.id
    WHERE $whereSql
");
$countStmt->execute($params);
$pagination = paginate((int) $countStmt->fetchColumn(), $page);

$stmt = $pdo->prepare("
    SELECT r.*, e.employee_code, e.first_name, e.last_name, e.position, e.date_of_birth,
           d.name AS department_name,
           TIMESTAMPDIFF(YEAR, e.date_of_birth, CURDATE()) AS current_age
    FROM retirements r
    JOIN employees e ON r.employee_id = e.id
    JOIN departments d ON e.department_id = d.id
    WHERE $whereSql
    ORDER BY r.retirement_date ASC
    LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
");
$stmt->execute($params);
$retirements = $stmt->fetchAll();

$pageTitle = __('retirement_management');
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-header">
    <h1><i class="bi bi-calendar-check me-2"></i><?= __('retirement_management') ?></h1>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <h6 class="card-title"><i class="bi bi-clock-history me-1"></i><?= __('near_retirement') ?></h6>
                <h2 class="mb-0"><?= (int) $totalNearRetirement ?></h2>
                <small><?= __('within_1_year') ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <h6 class="card-title"><i class="bi bi-exclamation-triangle me-1"></i><?= __('retirement_eligible') ?></h6>
                <h2 class="mb-0"><?= (int) $totalEligible ?></h2>
                <small><?= __('age_60_reached') ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h6 class="card-title"><i class="bi bi-calendar-event me-1"></i><?= __('retiring_this_month') ?></h6>
                <h2 class="mb-0"><?= (int) $retiringThisMonth ?></h2>
                <small><?= date('F Y') ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-secondary text-white">
            <div class="card-body">
                <h6 class="card-title"><i class="bi bi-person-check me-1"></i><?= __('retired') ?></h6>
                <h2 class="mb-0"><?= (int) $totalRetired ?></h2>
                <small><?= __('completed') ?></small>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title mb-0"><?= __('retirement_list') ?></h5>
            <a href="<?= baseUrl('modules/retirement/report.php') ?>" class="btn btn-outline-primary">
                <i class="bi bi-file-earmark-pdf me-1"></i><?= __('generate_report') ?>
            </a>
        </div>
        
        <form method="get" class="row g-2 mb-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" 
                       placeholder="<?= __('search') ?>" value="<?= e($search) ?>">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
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
            <div class="col-auto">
                <button type="submit" class="btn btn-primary"><?= __('filter') ?></button>
                <a href="<?= baseUrl('modules/retirement/index.php') ?>" class="btn btn-secondary"><?= __('reset') ?></a>
            </div>
        </form>
        
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th><?= __('employee_id') ?></th>
                        <th><?= __('employee_name') ?></th>
                        <th><?= __('department') ?></th>
                        <th><?= __('position') ?></th>
                        <th><?= __('current_age') ?></th>
                        <th><?= __('retirement_date') ?></th>
                        <th><?= __('status') ?></th>
                        <th><?= __('actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($retirements as $r): ?>
                    <tr>
                        <td><?= e($r['employee_code']) ?></td>
                        <td><?= e($r['first_name'] . ' ' . $r['last_name']) ?></td>
                        <td><?= e($r['department_name']) ?></td>
                        <td><?= e($r['position']) ?></td>
                        <td><?= (int) $r['current_age'] ?> <?= __('years') ?></td>
                        <td><?= formatDate($r['retirement_date']) ?></td>
                        <td>
                            <?php
                            $badge = match($r['status']) {
                                'near_retirement' => 'bg-warning',
                                'retirement_eligible' => 'bg-danger',
                                'retired' => 'bg-secondary',
                                default => 'bg-success'
                            };
                            ?>
                            <span class="badge <?= $badge ?>"><?= e(__($r['status'])) ?></span>
                        </td>
                        <td>
                            <a href="<?= baseUrl('modules/retirement/view.php?id=' . $r['employee_id']) ?>" 
                               class="btn btn-sm btn-outline-info">
                                <i class="bi bi-eye"></i> <?= __('view') ?>
                            </a>
                            <?php if ($r['status'] === 'retirement_eligible'): ?>
                            <a href="<?= baseUrl('modules/retirement/process.php?id=' . $r['employee_id']) ?>" 
                               class="btn btn-sm btn-outline-success">
                                <i class="bi bi-check-circle"></i> <?= __('process_retirement') ?>
                            </a>
                            <?php endif; ?>
                        </td>
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
        
        <?php
        $q = http_build_query(array_filter(['search' => $search, 'status' => $status ?: null]));
        echo renderPagination($pagination, baseUrl('modules/retirement/index.php') . ($q ? '?' . $q : ''));
        ?>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
