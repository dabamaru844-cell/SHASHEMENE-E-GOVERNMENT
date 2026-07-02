<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
requireModule('dashboard');

$user = currentUser();
$today = date('Y-m-d');

$stats = [
    'total_assets' => (int) $pdo->query('SELECT COUNT(*) FROM assets')->fetchColumn(),
    'assigned_assets' => (int) $pdo->query("SELECT COUNT(*) FROM assets WHERE status = 'assigned'")->fetchColumn(),
    'available_assets' => (int) $pdo->query("SELECT COUNT(*) FROM assets WHERE status = 'active'")->fetchColumn(),
    'maintenance_assets' => (int) $pdo->query("SELECT COUNT(*) FROM assets WHERE status = 'maintenance'")->fetchColumn(),
    'total_employees' => (int) $pdo->query('SELECT COUNT(*) FROM employees')->fetchColumn(),
    'active_employees' => (int) $pdo->query("SELECT COUNT(*) FROM employees WHERE employment_status = 'active'")->fetchColumn(),
    'present_today' => (int) $pdo->query("SELECT COUNT(*) FROM attendance WHERE attendance_date = '$today' AND status IN ('present','late')")->fetchColumn(),
    'absent_today' => (int) $pdo->query("SELECT COUNT(*) FROM attendance WHERE attendance_date = '$today' AND status = 'absent'")->fetchColumn(),
    'late_today' => (int) $pdo->query("SELECT COUNT(*) FROM attendance WHERE attendance_date = '$today' AND status = 'late'")->fetchColumn(),
];

$deptStats = $pdo->query(
    "SELECT d.name, COUNT(e.id) AS cnt FROM departments d
     LEFT JOIN employees e ON e.department_id = d.id AND e.employment_status = 'active'
     GROUP BY d.id ORDER BY cnt DESC"
)->fetchAll();

$maxDept = max(array_column($deptStats, 'cnt') ?: [1]);

$recentLogs = $pdo->query(
    'SELECT al.*, u.username FROM activity_logs al LEFT JOIN users u ON al.user_id = u.id ORDER BY al.created_at DESC LIMIT 8'
)->fetchAll();

$pageTitle = __('dashboard');
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1><?= __('welcome') ?>, <?= e($user['username']) ?>!</h1>
        <p class="text-muted mb-0"><?= e(__('app_tagline')) ?></p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon primary"><i class="bi bi-pc-display"></i></div>
                <div>
                    <div class="text-muted small"><?= __('total_assets') ?></div>
                    <div class="fs-4 fw-bold"><?= $stats['total_assets'] ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon success"><i class="bi bi-person-check"></i></div>
                <div>
                    <div class="text-muted small"><?= __('active_employees') ?></div>
                    <div class="fs-4 fw-bold"><?= $stats['active_employees'] ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon info"><i class="bi bi-calendar-check"></i></div>
                <div>
                    <div class="text-muted small"><?= __('present_today') ?></div>
                    <div class="fs-4 fw-bold"><?= $stats['present_today'] ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon warning"><i class="bi bi-clock-history"></i></div>
                <div>
                    <div class="text-muted small"><?= __('late_today') ?></div>
                    <div class="fs-4 fw-bold"><?= $stats['late_today'] ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card stat-card h-100">
            <div class="card-header"><?= __('assets') ?></div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2"><span><?= __('assigned_assets') ?></span><strong><?= $stats['assigned_assets'] ?></strong></div>
                <div class="d-flex justify-content-between mb-2"><span><?= __('available_assets') ?></span><strong><?= $stats['available_assets'] ?></strong></div>
                <div class="d-flex justify-content-between"><span><?= __('maintenance_assets') ?></span><strong><?= $stats['maintenance_assets'] ?></strong></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card h-100">
            <div class="card-header"><?= __('attendance') ?> - <?= formatDate($today) ?></div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2"><span><?= __('present_today') ?></span><strong class="text-success"><?= $stats['present_today'] ?></strong></div>
                <div class="d-flex justify-content-between mb-2"><span><?= __('absent_today') ?></span><strong class="text-danger"><?= $stats['absent_today'] ?></strong></div>
                <div class="d-flex justify-content-between"><span><?= __('late_today') ?></span><strong class="text-warning"><?= $stats['late_today'] ?></strong></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card h-100">
            <div class="card-header"><?= __('quick_actions') ?></div>
            <div class="card-body d-grid gap-2">
                <?php if (canAccess('attendance') && hasRole('admin', 'hr')): ?>
                <a href="<?= baseUrl('modules/attendance/create.php') ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-plus-circle me-1"></i><?= __('mark_attendance') ?></a>
                <?php endif; ?>
                <?php if (canAccess('employees') && hasRole('admin', 'hr')): ?>
                <a href="<?= baseUrl('modules/employees/create.php') ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-person-plus me-1"></i><?= __('add_employee') ?></a>
                <?php endif; ?>
                <?php if (canAccess('assets') && hasRole('admin', 'it')): ?>
                <a href="<?= baseUrl('modules/assets/create.php') ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-pc me-1"></i><?= __('add_asset') ?></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><?= __('employees_by_department') ?></div>
            <div class="card-body">
                <?php foreach ($deptStats as $dept): ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span><?= e($dept['name']) ?></span>
                        <span><?= (int) $dept['cnt'] ?></span>
                    </div>
                    <div class="chart-bar">
                        <div class="chart-bar-fill" style="width: <?= $maxDept > 0 ? round(($dept['cnt'] / $maxDept) * 100) : 0 ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><?= __('recent_activity') ?></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <tbody>
                        <?php foreach ($recentLogs as $log): ?>
                        <tr>
                            <td class="ps-3"><small><?= e($log['username'] ?? '-') ?></small></td>
                            <td><small><?= e($log['action']) ?> (<?= e($log['module']) ?>)</small></td>
                            <td class="pe-3 text-end"><small class="text-muted"><?= formatDate($log['created_at'], 'd/m H:i') ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentLogs)): ?>
                        <tr><td colspan="3" class="text-center text-muted py-3"><?= __('no_records') ?></td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
