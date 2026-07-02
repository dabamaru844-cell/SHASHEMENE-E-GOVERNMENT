<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $error = __('csrf_invalid');
    } else {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if ($new !== $confirm) {
            $error = __('password_mismatch');
        } elseif (strlen($new) < 8) {
            $error = __('error_occurred');
        } elseif (changePassword($pdo, (int) currentUser()['id'], $current, $new)) {
            flash('success', __('password_changed'));
            redirect(baseUrl('modules/dashboard/index.php'));
        } else {
            $error = __('invalid_current_password');
        }
    }
}

$pageTitle = __('change_password');
require __DIR__ . '/includes/header.php';
?>
<div class="page-header"><h1><?= __('change_password') ?></h1></div>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
                <form method="post">
                    <?= csrfField() ?>
                    <div class="mb-3">
                        <label class="form-label"><?= __('current_password') ?></label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= __('new_password') ?></label>
                        <input type="password" name="new_password" class="form-control" required minlength="8">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= __('confirm_password') ?></label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-brand"><?= __('submit') ?></button>
                    <a href="<?= baseUrl('modules/dashboard/index.php') ?>" class="btn btn-secondary"><?= __('cancel') ?></a>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
