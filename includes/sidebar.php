<?php

declare(strict_types=1);

$menuItems = [
    ['module' => 'dashboard', 'icon' => 'speedometer2', 'label' => __('dashboard'), 'url' => 'modules/dashboard/index.php'],
    ['module' => 'users', 'icon' => 'people', 'label' => __('users'), 'url' => 'modules/users/index.php', 'roles' => ['admin']],
    ['module' => 'employees', 'icon' => 'person-badge', 'label' => __('employees'), 'url' => 'modules/employees/index.php'],
    ['module' => 'assets', 'icon' => 'pc-display', 'label' => __('assets'), 'url' => 'modules/assets/index.php'],
    ['module' => 'attendance', 'icon' => 'calendar-check', 'label' => __('attendance'), 'url' => 'modules/attendance/index.php'],
    ['module' => 'leave', 'icon' => 'calendar-x', 'label' => __('leave'), 'url' => 'modules/leave/index.php'],
    ['module' => 'reports', 'icon' => 'file-earmark-bar-graph', 'label' => __('reports'), 'url' => 'modules/reports/index.php'],
    ['module' => 'notifications', 'icon' => 'bell', 'label' => __('notifications'), 'url' => 'modules/notifications/index.php'],
    ['module' => 'settings', 'icon' => 'gear', 'label' => __('settings'), 'url' => 'modules/settings/index.php', 'roles' => ['admin']],
];
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <img src="<?= assetUrl('img/logo.png') ?>" alt="<?= e(__('app_name')) ?>" class="sidebar-logo">
        <div class="brand-text">
            <strong>SHASHE</strong>
            <small>E GOVERNMENT</small>
        </div>
    </div>
    <nav class="sidebar-nav">
        <ul class="nav flex-column">
            <?php foreach ($menuItems as $item):
                if (!canAccess($item['module'])) continue;
                if (isset($item['roles']) && !hasRole(...$item['roles'])) continue;
                $active = str_contains($_SERVER['REQUEST_URI'] ?? '', $item['url']) ? ' active' : '';
            ?>
            <li class="nav-item">
                <a class="nav-link<?= $active ?>" href="<?= baseUrl($item['url']) ?>">
                    <i class="bi bi-<?= e($item['icon']) ?>"></i>
                    <span><?= e($item['label']) ?></span>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </nav>
</aside>
<div class="sidebar-overlay d-lg-none" id="sidebarOverlay"></div>
