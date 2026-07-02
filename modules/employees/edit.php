<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole('admin', 'hr');

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM employees WHERE id = ?');
$stmt->execute([$id]);
$emp = $stmt->fetch();
if (!$emp) {
    redirect(baseUrl('modules/employees/index.php'));
}

$departments = getDepartments($pdo);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $error = __('csrf_invalid');
    } else {
        $photo = $emp['photo'];
        if (!empty($_FILES['photo']['name'])) {
            $uploaded = uploadFile($_FILES['photo'], 'photos', ['jpg', 'jpeg', 'png']);
            if ($uploaded) $photo = $uploaded;
        }
        $pdo->prepare(
            'UPDATE employees SET first_name=?, last_name=?, gender=?, date_of_birth=?, department_id=?, position=?, employment_date=?, phone=?, email=?, address=?, photo=?, employment_status=? WHERE id=?'
        )->execute([
            trim($_POST['first_name'] ?? ''), trim($_POST['last_name'] ?? ''), $_POST['gender'] ?? 'male',
            $_POST['date_of_birth'] ?? '', (int) ($_POST['department_id'] ?? 0), trim($_POST['position'] ?? ''),
            $_POST['employment_date'] ?? '', trim($_POST['phone'] ?? ''), trim($_POST['email'] ?? ''),
            trim($_POST['address'] ?? ''), $photo, $_POST['employment_status'] ?? 'active', $id,
        ]);
        logActivity($pdo, (int) currentUser()['id'], 'update', 'employees', $id);
        flash('success', __('updated_success'));
        redirect(baseUrl('modules/employees/view.php?id=' . $id));
    }
}

$pageTitle = __('edit') . ' - ' . $emp['first_name'];
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-header"><h1><?= __('edit') ?> <?= e($emp['employee_code']) ?></h1></div>
<div class="card"><div class="card-body">
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data"><?= csrfField() ?>
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label"><?= __('first_name') ?></label><input type="text" name="first_name" class="form-control" required value="<?= e($emp['first_name']) ?>"></div>
            <div class="col-md-4"><label class="form-label"><?= __('last_name') ?></label><input type="text" name="last_name" class="form-control" required value="<?= e($emp['last_name']) ?>"></div>
            <div class="col-md-4"><label class="form-label"><?= __('gender') ?></label><select name="gender" class="form-select"><?php foreach (['male','female','other'] as $g): ?><option value="<?= $g ?>"<?= $emp['gender'] === $g ? ' selected' : '' ?>><?= __($g) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label class="form-label"><?= __('date_of_birth') ?></label><input type="date" name="date_of_birth" class="form-control" required value="<?= e($emp['date_of_birth']) ?>"></div>
            <div class="col-md-4"><label class="form-label"><?= __('department') ?></label><select name="department_id" class="form-select" required><?php foreach ($departments as $d): ?><option value="<?= (int) $d['id'] ?>"<?= (int) $d['id'] === (int) $emp['department_id'] ? ' selected' : '' ?>><?= e($d['name']) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label class="form-label"><?= __('position') ?></label><input type="text" name="position" class="form-control" required value="<?= e($emp['position']) ?>"></div>
            <div class="col-md-4"><label class="form-label"><?= __('employment_date') ?></label><input type="date" name="employment_date" class="form-control" required value="<?= e($emp['employment_date']) ?>"></div>
            <div class="col-md-4"><label class="form-label"><?= __('phone') ?></label><input type="text" name="phone" class="form-control" value="<?= e($emp['phone'] ?? '') ?>"></div>
            <div class="col-md-4"><label class="form-label"><?= __('email') ?></label><input type="email" name="email" class="form-control" value="<?= e($emp['email'] ?? '') ?>"></div>
            <div class="col-md-8"><label class="form-label"><?= __('address') ?></label><textarea name="address" class="form-control" rows="2"><?= e($emp['address'] ?? '') ?></textarea></div>
            <div class="col-md-4"><label class="form-label"><?= __('photo') ?></label><input type="file" name="photo" class="form-control" accept="image/jpeg,image/png"></div>
            <div class="col-md-4"><label class="form-label"><?= __('employment_status') ?></label><select name="employment_status" class="form-select"><?php foreach (['active','inactive','terminated','on_leave'] as $s): ?><option value="<?= $s ?>"<?= $emp['employment_status'] === $s ? ' selected' : '' ?>><?= __($s) ?></option><?php endforeach; ?></select></div>
        </div>
        <div class="mt-3"><button type="submit" class="btn btn-brand"><?= __('update') ?></button><a href="<?= baseUrl('modules/employees/index.php') ?>" class="btn btn-secondary"><?= __('cancel') ?></a></div>
    </form>
</div></div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
