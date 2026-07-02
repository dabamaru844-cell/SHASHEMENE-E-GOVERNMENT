<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (isLoggedIn()) {
    redirect(baseUrl('modules/dashboard/index.php'));
}

$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $error = __('csrf_invalid');
    } else {
        $email = trim($_POST['email'] ?? '');
        if (!validateEmail($email)) {
            $error = __('error_occurred');
        } else {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND is_active = 1');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                $pdo->prepare('DELETE FROM password_resets WHERE email = ?')->execute([$email]);
                $pdo->prepare('INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)')->execute([$email, $token, $expires]);
            }
            $message = __('password_recovery') . ' - Check your email if account exists.';
        }
    }
}

$pageTitle = __('password_recovery');
$bodyClass = 'login-page';
require __DIR__ . '/includes/header.php';
?>
<div class="login-card">
    <div class="login-header">
        <img src="<?= assetUrl('img/logo.png') ?>" alt="Logo" class="login-logo">
        <h1><?= __('password_recovery') ?></h1>
    </div>
    <div class="login-body">
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
        <form method="post">
            <?= csrfField() ?>
            <div class="mb-3">
                <label class="form-label"><?= __('email') ?></label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-brand w-100"><?= __('send_reset_link') ?></button>
            <a href="<?= baseUrl('login.php') ?>" class="btn btn-link w-100 mt-2"><?= __('back') ?></a>
        </form>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
