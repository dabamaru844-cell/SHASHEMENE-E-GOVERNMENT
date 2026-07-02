<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole('admin');

$roles = getRoles($pdo);
$employees = getEmployeesList($pdo, false);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $error = __('csrf_invalid');
    } else {
        $data = [
            'username' => trim($_POST['username'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'role_id' => (int) ($_POST['role_id'] ?? 0),
            'employee_id' => !empty($_POST['employee_id']) ? (int) $_POST['employee_id'] : null,
        ];
        if ($data['username'] === '' || !validateEmail($data['email']) || strlen($data['password']) < 8) {
            $error = __('error_occurred');
        } else {
            try {
                $id = registerUser($pdo, $data);
                logActivity($pdo, (int) currentUser()['id'], 'create', 'users', $id);
                flash('success', __('saved_success'));
                redirect(baseUrl('modules/users/index.php'));
            } catch (PDOException) {
                $error = __('error_occurred');
            }
        }
    }
}

$pageTitle = __('add_user');
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-header"><h1><?= __('add_user') ?></h1></div>
<div class="row justify-content-center"><div class="col-lg-8">
<div class="card"><div class="card-body">
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <form method="post"><?= csrfField() ?>
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label"><?= __('username') ?></label><input type="text" name="username" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label"><?= __('email') ?></label><input type="email" name="email" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label"><?= __('password') ?></label><input type="password" name="password" class="form-control" required minlength="8"></div>
            <div class="col-md-6"><label class="form-label"><?= __('role') ?></label>
                <select name="role_id" class="form-select" required>
                    <?php foreach ($roles as $r): ?><option value="<?= (int) $r['id'] ?>"><?= e($r['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6"><label class="form-label"><?= __('employees') ?></label>
                <select name="employee_id" class="form-select"><option value=""><?= __('select') ?></option>
                    <?php foreach ($employees as $e): ?><option value="<?= (int) $e['id'] ?>"><?= e($e['employee_code'] . ' - ' . $e['first_name'] . ' ' . $e['last_name']) ?></option><?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="mt-3"><button type="submit" class="btn btn-brand"><?= __('save') ?></button>
        <a href="<?= baseUrl('modules/users/index.php') ?>" class="btn btn-secondary"><?= __('cancel') ?></a></div>
    </form>
</div></div></div></div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
