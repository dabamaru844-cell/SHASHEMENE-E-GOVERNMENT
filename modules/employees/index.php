<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
requireModule('employees');

$page = max(1, (int) ($_GET['page'] ?? 1));
$search = trim($_GET['search'] ?? '');
$department = (int) ($_GET['department'] ?? 0);
$departments = getDepartments($pdo);

$where = ['1=1'];
$params = [];
if ($search !== '') {
    $where[] = '(e.employee_code LIKE ? OR e.first_name LIKE ? OR e.last_name LIKE ?)';
    array_push($params, "%$search%", "%$search%", "%$search%");
}
if ($department > 0) {
    $where[] = 'e.department_id = ?';
    $params[] = $department;
}
$whereSql = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM employees e WHERE $whereSql");
$countStmt->execute($params);
$pagination = paginate((int) $countStmt->fetchColumn(), $page);

$stmt = $pdo->prepare(
    "SELECT e.*, d.name AS department_name FROM employees e JOIN departments d ON e.department_id = d.id
     WHERE $whereSql ORDER BY e.last_name, e.first_name LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}"
);
$stmt->execute($params);
$employees = $stmt->fetchAll();

$pageTitle = __('employees');
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h1><?= __('employees') ?></h1>
    <?php if (hasRole('admin', 'hr')): ?>
    <a href="<?= baseUrl('modules/employees/create.php') ?>" class="btn btn-brand"><i class="bi bi-plus-lg me-1"></i><?= __('add_employee') ?></a>
    <?php endif; ?>
</div>
<div class="card"><div class="card-body">
    <form method="get" class="row g-2 mb-3">
        <div class="col-md-3"><input type="text" name="search" class="form-control" placeholder="<?= __('search') ?>" value="<?= e($search) ?>"></div>
        <div class="col-md-3"><select name="department" class="form-select"><option value=""><?= __('all') ?> <?= __('department') ?></option>
            <?php foreach ($departments as $d): ?><option value="<?= (int) $d['id'] ?>"<?= $department === (int) $d['id'] ? ' selected' : '' ?>><?= e($d['name']) ?></option><?php endforeach; ?>
        </select></div>
        <div class="col-auto"><button type="submit" class="btn btn-primary"><?= __('filter') ?></button></div>
    </form>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead><tr>
                <th><?= __('employee_id') ?></th><th><?= __('first_name') ?></th><th><?= __('last_name') ?></th>
                <th><?= __('department') ?></th><th><?= __('position') ?></th><th><?= __('status') ?></th><th><?= __('actions') ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($employees as $emp): ?>
            <tr>
                <td><?= e($emp['employee_code']) ?></td>
                <td><?= e($emp['first_name']) ?></td>
                <td><?= e($emp['last_name']) ?></td>
                <td><?= e($emp['department_name']) ?></td>
                <td><?= e($emp['position']) ?></td>
                <td><span class="badge bg-<?= $emp['employment_status'] === 'active' ? 'success' : 'secondary' ?>"><?= e(__($emp['employment_status'])) ?></span></td>
                <td>
                    <a href="<?= baseUrl('modules/employees/view.php?id=' . $emp['id']) ?>" class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i></a>
                    <?php if (hasRole('admin', 'hr')): ?>
                    <a href="<?= baseUrl('modules/employees/edit.php?id=' . $emp['id']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                    <a href="<?= baseUrl('modules/employees/delete.php?id=' . $emp['id']) ?>" class="btn btn-sm btn-outline-danger" data-confirm="<?= e(__('delete_confirm')) ?>"><i class="bi bi-trash"></i></a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($employees)): ?><tr><td colspan="7" class="text-center text-muted"><?= __('no_records') ?></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
    $q = http_build_query(array_filter(['search' => $search, 'department' => $department ?: null]));
    echo renderPagination($pagination, baseUrl('modules/employees/index.php') . ($q ? '?' . $q : ''));
    ?>
</div></div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
