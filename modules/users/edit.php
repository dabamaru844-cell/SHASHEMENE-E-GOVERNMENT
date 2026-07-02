<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole('admin');

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$id]);
$user = $stmt->fetch();
if (!$user) {
    flash('error', __('error_occurred'));
    redirect(baseUrl('modules/users/index.php'));
}

$roles = getRoles($pdo);
$employees = getEmployeesList($pdo, false);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $error = __('csrf_invalid');
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $roleId = (int) ($_POST['role_id'] ?? 0);
        $employeeId = !empty($_POST['employee_id']) ? (int) $_POST['employee_id'] : null;
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $password = $_POST['password'] ?? '';

        if ($username === '' || !validateEmail($email)) {
            $error = __('error_occurred');
        } else {
            try {
                if ($password !== '') {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $pdo->prepare('UPDATE users SET username=?, email=?, password=?, role_id=?, employee_id=?, is_active=? WHERE id=?')
                        ->execute([$username, $email, $hash, $roleId, $employeeId, $isActive, $id]);
                } else {
                    $pdo->prepare('UPDATE users SET username=?, email=?, role_id=?, employee_id=?, is_active=? WHERE id=?')
                        ->execute([$username, $email, $roleId, $employeeId, $isActive, $id]);
                }
                logActivity($pdo, (int) currentUser()['id'], 'update', 'users', $id);
                flash('success', __('updated_success'));
                redirect(baseUrl('modules/users/index.php'));
            } catch (PDOException) {
                $error = __('error_occurred');
            }
        }
    }
}

$pageTitle = __('edit_user');
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-header"><h1><?= __('edit_user') ?></h1></div>
<div class="row justify-content-center"><div class="col-lg-8">
<div class="card"><div class="card-body">
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <form method="post"><?= csrfField() ?>
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label"><?= __('username') ?></label><input type="text" name="username" class="form-control" required value="<?= e($user['username']) ?>"></div>
            <div class="col-md-6"><label class="form-label"><?= __('email') ?></label><input type="email" name="email" class="form-control" required value="<?= e($user['email']) ?>"></div>
            <div class="col-md-6"><label class="form-label"><?= __('new_password') ?> (<?= __('select') ?>)</label><input type="password" name="password" class="form-control" minlength="8"></div>
            <div class="col-md-6"><label class="form-label"><?= __('role') ?></label>
                <select name="role_id" class="form-select" required>
                    <?php foreach ($roles as $r): ?><option value="<?= (int) $r['id'] ?>"<?= (int) $r['id'] === (int) $user['role_id'] ? ' selected' : '' ?>><?= e($r['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6"><label class="form-label"><?= __('employees') ?></label>
                <select name="employee_id" class="form-select"><option value=""><?= __('select') ?></option>
                    <?php foreach ($employees as $e): ?><option value="<?= (int) $e['id'] ?>"<?= (int) $e['id'] === (int) $user['employee_id'] ? ' selected' : '' ?>><?= e($e['employee_code'] . ' - ' . $e['first_name'] . ' ' . $e['last_name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6"><div class="form-check mt-4"><input type="checkbox" name="is_active" class="form-check-input" id="active"<?= $user['is_active'] ? ' checked' : '' ?>><label class="form-check-label" for="active"><?= __('is_active') ?></label></div></div>
        </div>
        <div class="mt-3"><button type="submit" class="btn btn-brand"><?= __('update') ?></button>
        <a href="<?= baseUrl('modules/users/index.php') ?>" class="btn btn-secondary"><?= __('cancel') ?></a></div>
    </form>
</div></div></div></div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
