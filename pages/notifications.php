<?php
require_once __DIR__ . '/../includes/init.php';
$user = require_login();

if (get('mark') === 'all') {
    db()->prepare('UPDATE notifications SET lu = 1 WHERE user_id = ?')->execute([$user['id']]);
    flash('success', 'Toutes les notifications marquées comme lues.');
    redirect('pages/notifications.php');
}

$id = (int) get('lire', 0);
if ($id) {
    $stmt = db()->prepare('SELECT * FROM notifications WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $user['id']]);
    $n = $stmt->fetch();
    if ($n) {
        db()->prepare('UPDATE notifications SET lu = 1 WHERE id = ?')->execute([$id]);
        if ($n['lien']) {
            redirect($n['lien']);
        }
    }
    redirect('pages/notifications.php');
}

$stmt = db()->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 100');
$stmt->execute([$user['id']]);
$notifs = $stmt->fetchAll();

$pageTitle = 'Notifications';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div class="card-header">
        <h2>Centre de notifications</h2>
        <a class="btn btn-sm btn-secondary" href="?mark=all">Tout marquer comme lu</a>
    </div>
    <?php if (!$notifs): ?>
        <div class="empty">Aucune notification.</div>
    <?php else: ?>
        <?php foreach ($notifs as $n): ?>
            <div class="notif-item <?= $n['lu'] ? '' : 'unread' ?>">
                <div class="notif-item__body">
                    <strong><?= e($n['titre']) ?></strong>
                    <div><?= e($n['message']) ?></div>
                    <div class="meta"><?= e(format_datetime($n['created_at'])) ?></div>
                </div>
                <?php if (!$n['lu'] || $n['lien']): ?>
                    <a class="btn btn-sm btn-secondary" href="?lire=<?= (int) $n['id'] ?>">Voir</a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
