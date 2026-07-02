<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
requireModule('notifications');

$user = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    if (isset($_POST['mark_all'])) {
        $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?')->execute([(int) $user['id']]);
    } else {
        $id = (int) ($_POST['id'] ?? 0);
        $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?')->execute([$id, (int) $user['id']]);
    }
    redirect(baseUrl('modules/notifications/index.php'));
}

$stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50');
$stmt->execute([(int) $user['id']]);
$notifications = $stmt->fetchAll();

$pageTitle = __('notifications');
require __DIR__ . '/../../includes/header.php';
?>
<div class="page-header d-flex justify-content-between align-items-center">
    <h1><?= __('notifications') ?></h1>
    <form method="post"><?= csrfField() ?><input type="hidden" name="mark_all" value="1">
        <button type="submit" class="btn btn-outline-primary btn-sm"><?= __('mark_all_read') ?></button>
    </form>
</div>
<div class="card"><div class="list-group list-group-flush">
    <?php foreach ($notifications as $n): ?>
    <div class="list-group-item<?= !$n['is_read'] ? ' list-group-item-warning' : '' ?>">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h6 class="mb-1"><?= e($n['title']) ?></h6>
                <p class="mb-1 small"><?= e($n['message']) ?></p>
                <small class="text-muted"><?= formatDate($n['created_at'], 'd M Y H:i') ?></small>
            </div>
            <?php if ($n['link']): ?><a href="<?= e($n['link']) ?>" class="btn btn-sm btn-link"><?= __('view') ?></a><?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($notifications)): ?>
    <div class="list-group-item text-center text-muted py-4"><?= __('no_records') ?></div>
    <?php endif; ?>
</div></div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
