<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole('admin');

$page = max(1, (int) ($_GET['page'] ?? 1));
$search = trim($_GET['search'] ?? '');

$where = '1=1';
$params = [];
if ($search !== '') {
    $where .= ' AND (u.username LIKE ? OR u.email LIKE ?)';
    $params = ["%$search%", "%$search%"];
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users u WHERE $where");
$countStmt->execute($params);
$pagination = paginate((int) $countStmt->fetchColumn(), $page);

$stmt = $pdo->prepare(
    "SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON u.role_id = r.id
     WHERE $where ORDER BY u.created_at DESC LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}"
);
$stmt->execute($params);
$users = $stmt->fetchAll();

$pageTitle = __('users');
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h1><?= __('users') ?></h1>
    <a href="<?= baseUrl('modules/users/create.php') ?>" class="btn btn-brand"><i class="bi bi-plus-lg me-1"></i><?= __('add_user') ?></a>
</div>
<div class="card">
    <div class="card-body">
        <form method="get" class="row g-2 mb-3">
            <div class="col-md-4"><input type="text" name="search" class="form-control" placeholder="<?= __('search') ?>" value="<?= e($search) ?>"></div>
            <div class="col-auto"><button type="submit" class="btn btn-primary"><?= __('search') ?></button></div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr>
                    <th>#</th><th><?= __('username') ?></th><th><?= __('email') ?></th><th><?= __('role') ?></th><th><?= __('is_active') ?></th><th><?= __('actions') ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= (int) $u['id'] ?></td>
                    <td><?= e($u['username']) ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td><?= e($u['role_name']) ?></td>
                    <td><span class="badge bg-<?= $u['is_active'] ? 'success' : 'secondary' ?>"><?= $u['is_active'] ? __('yes') : __('no') ?></span></td>
                    <td>
                        <a href="<?= baseUrl('modules/users/edit.php?id=' . $u['id']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                        <?php if ((int) $u['id'] !== (int) currentUser()['id']): ?>
                        <a href="<?= baseUrl('modules/users/delete.php?id=' . $u['id']) ?>" class="btn btn-sm btn-outline-danger" data-confirm="<?= e(__('delete_confirm')) ?>"><i class="bi bi-trash"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?><tr><td colspan="6" class="text-center text-muted"><?= __('no_records') ?></td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <?= renderPagination($pagination, baseUrl('modules/users/index.php') . ($search ? '?search=' . urlencode($search) : '')) ?>
    </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
