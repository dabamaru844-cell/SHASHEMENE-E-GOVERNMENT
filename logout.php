<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

logoutUser($pdo);
flash('success', __('logout_success'));
redirect(baseUrl('login.php'));
