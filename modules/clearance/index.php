<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole('admin', 'hr');

$page = max(1, (int) ($_GET['page'] ?? 1));
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';

$where = ['1=1'];
$params = [];

if ($search !== '') {
    $where[] = '(e.employee_code LIKE ? OR e.first_name LIKE ? OR e.last_name LIKE ?)';
    array_push($params, "%$search%", "%$search%", "%$search%");
}

if ($status && in_array($status, ['pending', 'in_progress', 'completed', 'cancelled'], true)) {
    $where[] = 'c.overall_status = ?';
    $params[] = $status;
}

$whereSql = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM clearances c JOIN employees e ON c.employee_id = e.id WHERE $whereSql");
$countStmt->execute($params);
$pagination = paginate((int) $countStmt->fetchColumn(), $page);

$stmt = $pdo->prepare("
    SELECT c.*, e.employee_code, e.first_name, e.last_name, e.position, d.name AS department_name
    FROM clearances c
    JOIN employees e ON c.employee_id = e.id
    JOIN departments d ON e.department_id = d.id
    WHERE $whereSql
    ORDER BY c.created_at DESC
    LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
");
$stmt->execute($params);
$clearances = $stmt->fetchAll();

// Get statistics
$totalPending = $pdo->query("SELECT COUNT(*) FROM clearances WHERE overall_status = 'pending'")->fetchColumn();
$totalInProgress = $pdo->query("SELECT COUNT(*) FROM clearances WHERE overall_status = 'in_progress'")->fetchColumn();
$totalCompleted = $pdo->query("SELECT COUNT(*) FROM clearances WHERE overall_status = 'completed'")->fetchColumn();

$pageTitle = __('clearance_management');
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-header d-flex justify-content-between align-items-center">
    <h1><i class="bi bi-clipboard-check me-2"></i><?= __('clearance_management') ?></h1>
    <a href="<?= baseUrl('modules/clearance/create.php') ?>" class="btn btn-brand">
        <i class="bi bi-plus-lg me-1"></i><?= __('create_clearance') ?>
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <h6 class="card-title"><i class="bi bi-hourglass me-1"></i><?= __('pending') ?></h6>
                <h2 class="mb-0"><?= (int) $totalPending ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h6 class="card-title"><i class="bi bi-arrow-repeat me-1"></i><?= __('in_progress') ?></h6>
                <h2 class="mb-0"><?= (int) $totalInProgress ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h6 class="card-title"><i class="bi bi-check-circle me-1"></i><?= __('completed') ?></h6>
                <h2 class="mb-0"><?= (int) $totalCompleted ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="get" class="row g-2 mb-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="<?= __('search') ?>" value="<?= e($search) ?>">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value=""><?= __('all_status') ?></option>
                    <option value="pending"<?= $status === 'pending' ? ' selected' : '' ?>><?= __('pending') ?></option>
                    <option value="in_progress"<?= $status === 'in_progress' ? ' selected' : '' ?>><?= __('in_progress') ?></option>
                    <option value="completed"<?= $status === 'completed' ? ' selected' : '' ?>><?= __('completed') ?></option>
                    <option value="cancelled"<?= $status === 'cancelled' ? ' selected' : '' ?>><?= __('cancelled') ?></option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary"><?= __('filter') ?></button>
            </div>
        </form>
        
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th><?= __('employee_id') ?></th>
                        <th><?= __('employee_name') ?></th>
                        <th><?= __('department') ?></th>
                        <th><?= __('exit_reason') ?></th>
                        <th><?= __('exit_date') ?></th>
                        <th><?= __('progress') ?></th>
                        <th><?= __('status') ?></th>
                        <th><?= __('actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($clearances as $c): ?>
                    <tr>
                        <td><?= e($c['employee_code']) ?></td>
                        <td><?= e($c['first_name'] . ' ' . $c['last_name']) ?></td>
                        <td><?= e($c['department_name']) ?></td>
                        <td><?= e(__($c['exit_reason'])) ?></td>
                        <td><?= formatDate($c['exit_date']) ?></td>
                        <td>
                            <?php
                            $approved = 0;
                            $total = 5;
                            if ($c['hr_status'] === 'approved') $approved++;
                            if ($c['it_status'] === 'approved') $approved++;
                            if ($c['finance_status'] === 'approved') $approved++;
                            if ($c['store_status'] === 'approved') $approved++;
                            if ($c['administration_status'] === 'approved') $approved++;
                            $percentage = round(($approved / $total) * 100);
                            ?>
                            <div class="progress" style="height: 25px;">
                                <div class="progress-bar" role="progressbar" 
                                     style="width: <?= $percentage ?>%" 
                                     aria-valuenow="<?= $percentage ?>" aria-valuemin="0" aria-valuemax="100">
                                    <?= $percentage ?>%
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php
                            $badge = match($c['overall_status']) {
                                'pending' => 'bg-warning',
                                'in_progress' => 'bg-info',
                                'completed' => 'bg-success',
                                'cancelled' => 'bg-secondary',
                                default => 'bg-secondary'
                            };
                            ?>
                            <span class="badge <?= $badge ?>"><?= e(__($c['overall_status'])) ?></span>
                        </td>
                        <td>
                            <a href="<?= baseUrl('modules/clearance/view.php?id=' . $c['id']) ?>" 
                               class="btn btn-sm btn-outline-info">
                                <i class="bi bi-eye"></i>
                            </a>
                            <?php if ($c['overall_status'] !== 'completed'): ?>
                            <a href="<?= baseUrl('modules/clearance/approve.php?id=' . $c['id']) ?>" 
                               class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-check"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($clearances)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted"><?= __('no_records') ?></td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php
        $q = http_build_query(array_filter(['search' => $search, 'status' => $status ?: null]));
        echo renderPagination($pagination, baseUrl('modules/clearance/index.php') . ($q ? '?' . $q : ''));
        ?>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
