<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
requireModule('profile');

$user = currentUser();
$employee = null;

if (!empty($user['employee_id'])) {
    $stmt = $pdo->prepare('SELECT e.*, d.name AS department_name FROM employees e JOIN departments d ON e.department_id = d.id WHERE e.id = ?');
    $stmt->execute([(int) $user['employee_id']]);
    $employee = $stmt->fetch();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $employee && verifyCsrf()) {
    $pdo->prepare('UPDATE employees SET phone=?, email=?, address=? WHERE id=?')->execute([
        trim($_POST['phone'] ?? ''),
        trim($_POST['email'] ?? ''),
        trim($_POST['address'] ?? ''),
        (int) $employee['id'],
    ]);
    logActivity($pdo, (int) $user['id'], 'update', 'profile', (int) $employee['id']);
    flash('success', __('updated_success'));
    redirect(baseUrl('modules/profile/index.php'));
}

$pageTitle = __('profile');
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-header"><h1><?= __('profile') ?></h1></div>
<div class="row g-3">
    <div class="col-md-4">
        <div class="card text-center"><div class="card-body">
            <?php if ($employee && $employee['photo']): ?>
            <img src="<?= assetUrl($employee['photo']) ?>" alt="" class="profile-photo mb-3">
            <?php else: ?>
            <div class="profile-photo bg-secondary d-inline-flex align-items-center justify-content-center mb-3"><i class="bi bi-person fs-1 text-white"></i></div>
            <?php endif; ?>
            <h5><?= e($user['username']) ?></h5>
            <p class="text-muted"><?= e($user['role_name'] ?? $user['role_slug']) ?></p>
            <p class="small"><?= e($user['email']) ?></p>
        </div></div>
    </div>
    <div class="col-md-8">
        <?php if ($employee): ?>
        <div class="card"><div class="card-header"><?= __('personal_info') ?></div><div class="card-body">
            <form method="post"><?= csrfField() ?>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label"><?= __('employee_id') ?></label><input type="text" class="form-control" readonly value="<?= e($employee['employee_code']) ?>"></div>
                    <div class="col-md-6"><label class="form-label"><?= __('department') ?></label><input type="text" class="form-control" readonly value="<?= e($employee['department_name']) ?>"></div>
                    <div class="col-md-6"><label class="form-label"><?= __('position') ?></label><input type="text" class="form-control" readonly value="<?= e($employee['position']) ?>"></div>
                    <div class="col-md-6"><label class="form-label"><?= __('phone') ?></label><input type="text" name="phone" class="form-control" value="<?= e($employee['phone'] ?? '') ?>"></div>
                    <div class="col-md-6"><label class="form-label"><?= __('email') ?></label><input type="email" name="email" class="form-control" value="<?= e($employee['email'] ?? '') ?>"></div>
                    <div class="col-12"><label class="form-label"><?= __('address') ?></label><textarea name="address" class="form-control" rows="2"><?= e($employee['address'] ?? '') ?></textarea></div>
                </div>
                <button type="submit" class="btn btn-brand mt-3"><?= __('update') ?></button>
            </form>
        </div></div>
        <?php else: ?>
        <div class="card"><div class="card-body">
            <p class="text-muted"><?= __('no_records') ?></p>
            <a href="<?= baseUrl('change-password.php') ?>" class="btn btn-brand"><?= __('change_password') ?></a>
        </div></div>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
