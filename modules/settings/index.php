<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
requireRole('admin');

$settings = [];
foreach ($pdo->query('SELECT setting_key, setting_value FROM system_settings')->fetchAll() as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    foreach (['site_name', 'default_language'] as $key) {
        if (isset($_POST[$key])) {
            $pdo->prepare('UPDATE system_settings SET setting_value = ? WHERE setting_key = ?')->execute([trim($_POST[$key]), $key]);
        }
    }
    logActivity($pdo, (int) currentUser()['id'], 'update', 'settings');
    flash('success', __('updated_success'));
    redirect(baseUrl('modules/settings/index.php'));
}

$pageTitle = __('settings');
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-header"><h1><?= __('system_settings') ?></h1></div>
<div class="row justify-content-center"><div class="col-md-8">
<div class="card"><div class="card-body">
    <form method="post"><?= csrfField() ?>
        <div class="mb-3"><label class="form-label"><?= __('site_name') ?></label>
            <input type="text" name="site_name" class="form-control" value="<?= e($settings['site_name'] ?? '') ?>">
        </div>
        <div class="mb-3"><label class="form-label"><?= __('default_language') ?></label>
            <select name="default_language" class="form-select">
                <?php foreach ($config['supported_locales'] as $lang): ?>
                <option value="<?= $lang ?>"<?= ($settings['default_language'] ?? 'en') === $lang ? ' selected' : '' ?>><?= e($config['locale_names'][$lang]) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-brand"><?= __('save') ?></button>
    </form>
    <hr>
    <h6><?= __('password_recovery') ?></h6>
    <?php $dbCfg = require __DIR__ . '/../../config/database.php'; ?>
    <p class="text-muted small">Database: <?= e($dbCfg['database']) ?> | PHP <?= PHP_VERSION ?></p>
</div></div></div></div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
