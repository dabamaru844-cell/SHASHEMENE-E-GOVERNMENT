<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (isLoggedIn()) {
    redirect(baseUrl('modules/dashboard/index.php'));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $error = __('csrf_invalid');
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($username === '' || $password === '') {
            $error = __('login_failed');
        } elseif (loginUser($pdo, $username, $password)) {
            flash('success', __('login_success'));
            redirect(baseUrl('modules/dashboard/index.php'));
        } else {
            $error = __('login_failed');
        }
    }
}

$pageTitle = __('login');
$bodyClass = 'login-page';
require __DIR__ . '/includes/header.php';
?>
<div class="login-card">
    <div class="login-header">
        <img src="<?= assetUrl('img/logo.png') ?>" alt="Logo" class="login-logo">
        <h1><?= e(__('app_name')) ?></h1>
        <p class="mb-0 mt-2 opacity-75 small"><?= e(__('app_tagline')) ?></p>
    </div>
    <div class="login-body">
        <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>
        <?= renderFlash() ?>
        <form method="post" action="">
            <?= csrfField() ?>
            <div class="mb-3">
                <label class="form-label"><?= __('username') ?></label>
                <input type="text" name="username" class="form-control" required autofocus value="<?= e($_POST['username'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label"><?= __('password') ?></label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <a href="<?= baseUrl('forgot-password.php') ?>" class="small"><?= __('forgot_password') ?></a>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-translate"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <?php foreach ($config['supported_locales'] as $lang): ?>
                        <li><a class="dropdown-item" href="<?= e(langUrl($lang)) ?>"><?= e($config['locale_names'][$lang]) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <button type="submit" class="btn btn-brand w-100 py-2"><?= __('login') ?></button>
        </form>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
