<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (isLoggedIn()) {
    redirect(baseUrl('modules/dashboard/index.php'));
}
redirect(baseUrl('login.php'));
