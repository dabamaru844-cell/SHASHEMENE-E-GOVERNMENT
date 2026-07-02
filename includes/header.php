<?php

declare(strict_types=1);

$config = require __DIR__ . '/../config/app.php';
$pageTitle = $pageTitle ?? __('dashboard');
$bodyClass = $bodyClass ?? '';
$user = currentUser();
$notifCount = $user ? getUnreadNotificationCount($pdo, (int) $user['id']) : 0;
?>
<!DOCTYPE html>
<html lang="<?= e($locale ?? 'en') ?>" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e(__('app_tagline')) ?>">
    <title><?= e($pageTitle) ?> - <?= e(__('app_name')) ?></title>
    <link rel="icon" type="image/png" href="<?= assetUrl('img/logo.png') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= assetUrl('css/style.css') ?>" rel="stylesheet">
</head>
<body class="<?= e($bodyClass) ?>">
<?php if ($user): ?>
<div class="app-wrapper">
    <?php require __DIR__ . '/sidebar.php'; ?>
    <div class="main-content">
        <nav class="topbar navbar navbar-dark">
            <div class="container-fluid">
                <button class="btn btn-link text-white d-lg-none" id="sidebarToggle" type="button">
                    <i class="bi bi-list fs-4"></i>
                </button>
                <div class="d-flex align-items-center ms-auto gap-3">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-light dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bi bi-translate"></i> <?= e($config['locale_names'][$locale] ?? 'EN') ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php foreach ($config['supported_locales'] as $lang): ?>
                            <li><a class="dropdown-item<?= $locale === $lang ? ' active' : '' ?>" href="<?= e(langUrl($lang)) ?>"><?= e($config['locale_names'][$lang]) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <a href="<?= baseUrl('modules/notifications/index.php') ?>" class="btn btn-sm btn-outline-light position-relative">
                        <i class="bi bi-bell"></i>
                        <?php if ($notifCount > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $notifCount ?></span>
                        <?php endif; ?>
                    </a>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-light dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?= e($user['username']) ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= baseUrl('modules/profile/index.php') ?>"><i class="bi bi-person me-2"></i><?= __('profile') ?></a></li>
                            <li><a class="dropdown-item" href="<?= baseUrl('change-password.php') ?>"><i class="bi bi-key me-2"></i><?= __('change_password') ?></a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= baseUrl('logout.php') ?>"><i class="bi bi-box-arrow-right me-2"></i><?= __('logout') ?></a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
        <main class="content-area p-3 p-md-4">
            <?= renderFlash() ?>
<?php else: ?>
<main>
<?php endif; ?>
