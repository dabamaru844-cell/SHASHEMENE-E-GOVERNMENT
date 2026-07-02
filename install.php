<?php

declare(strict_types=1);

/**
 * SHASHE E GOVERNMENT - Database Installer
 * Run once: http://localhost/Shashemene%20e%20gevernment/install.php
 * Delete this file after successful installation.
 */

$config = require __DIR__ . '/config/database.php';
$schemaFile = __DIR__ . '/database/schema.sql';
$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $dsn = sprintf('mysql:host=%s;port=%d;charset=%s', $config['host'], $config['port'], $config['charset']);
        $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
        $sql = file_get_contents($schemaFile);
        if ($sql === false) {
            throw new RuntimeException('Could not read schema file.');
        }
        $pdo->exec($sql);
        $success = true;
        $message = 'Database installed successfully! Default login: admin / Admin@123';
    } catch (Throwable $e) {
        $message = 'Installation failed: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install - SHASHE E GOVERNMENT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body text-center p-4">
                    <img src="assets/img/logo.png" alt="Logo" width="80" class="mb-3 rounded-circle">
                    <h2 class="h4">SHASHE E GOVERNMENT</h2>
                    <p class="text-muted">Database Installation</p>
                    <?php if ($message): ?>
                    <div class="alert alert-<?= $success ? 'success' : 'danger' ?>"><?= htmlspecialchars($message) ?></div>
                    <?php if ($success): ?>
                    <a href="login.php" class="btn btn-warning">Go to Login</a>
                    <?php endif; ?>
                    <?php else: ?>
                    <p>Click below to create the database and seed initial data.</p>
                    <form method="post">
                        <button type="submit" class="btn btn-warning btn-lg">Install Database</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
