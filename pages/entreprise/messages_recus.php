<?php
require_once __DIR__ . '/../../includes/init.php';
$user = require_role('entreprise');
$entrepriseId = get_entreprise_id_for_user($user);
if (!$entrepriseId) {
    flash('error', 'Entreprise introuvable.');
    redirect(role_home('entreprise'));
}

$lire = (int) get('lire', 0);
if ($lire) {
    $stmt = db()->prepare('SELECT * FROM contact_public WHERE id = ? AND entreprise_id = ?');
    $stmt->execute([$lire, $entrepriseId]);
    if ($stmt->fetch()) {
        db()->prepare('UPDATE contact_public SET lu = 1 WHERE id = ?')->execute([$lire]);
    }
}

$messages = db()->prepare('SELECT * FROM contact_public WHERE entreprise_id = ? ORDER BY created_at DESC LIMIT 100');
$messages->execute([$entrepriseId]);
$rows = $messages->fetchAll();

$pageTitle = 'Messages reçus';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="card">
    <div class="card-header">
        <h2>Messages publics</h2>
        <a class="btn btn-sm btn-secondary" href="<?= url('pages/entreprise/employes.php') ?>">Visiteurs</a>
    </div>
    <p class="help-text help-text--flush">
        Créez le compte, puis envoyez la notification (statut du rendez-vous + identifiants).
    </p>

    <?php if (!$rows): ?>
        <div class="empty">Aucun message reçu pour le moment.</div>
    <?php else: ?>
        <div class="msg-public-list">
            <?php foreach ($rows as $m): ?>
                <?php
                $dejaVisiteur = false;
                if (!empty($m['expediteur_user_id'])) {
                    $vu = get_user_by_id((int) $m['expediteur_user_id']);
                    $dejaVisiteur = $vu && $vu['role'] === 'employe' && (int) ($vu['entreprise_id'] ?? 0) === (int) $entrepriseId;
                }
                $rdv = rdv_status_for_contact_email((int) $entrepriseId, (string) $m['email']);
                ?>
                <article class="msg-public-card<?= $m['lu'] ? '' : ' is-unread' ?>">
                    <div class="msg-public-card__body">
                        <div class="msg-public-card__head">
                            <strong><?= e($m['sujet']) ?></strong>
                            <span class="meta"><?= e(format_datetime($m['created_at'])) ?></span>
                        </div>
                        <div class="msg-public-card__from">
                            <?= e($m['nom']) ?> · <?= e($m['email']) ?>
                            <?php if ($m['telephone']): ?> · <?= e($m['telephone']) ?><?php endif; ?>
                        </div>
                        <p class="msg-public-card__text"><?= nl2br(e($m['message'])) ?></p>
                        <?php if ($rdv): ?>
                            <div class="msg-public-card__rdv">RDV : <?= statut_badge($rdv['statut']) ?> <?= e($rdv['titre']) ?></div>
                        <?php endif; ?>
                        <?php if ($dejaVisiteur): ?>
                            <span class="tag-soft"><?= !empty($m['notif_envoyee']) ? 'Notification envoyée' : 'Compte prêt — à notifier' ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="msg-public-card__actions">
                        <?php if (!$dejaVisiteur): ?>
                            <a class="btn btn-sm btn-primary" href="<?= url('pages/entreprise/visiteur_creer.php?contact_id=' . (int) $m['id']) ?>">Créer un compte</a>
                        <?php else: ?>
                            <a class="btn btn-sm btn-primary" href="<?= url('pages/entreprise/visiteur_notifier.php?contact_id=' . (int) $m['id']) ?>">Notifier</a>
                        <?php endif; ?>
                        <form method="post" action="<?= url('actions/contact_public_delete.php') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="contact_id" value="<?= (int) $m['id'] ?>">
                            <button class="btn btn-sm btn-ghost" type="submit" data-confirm="Supprimer ce message ?">Supprimer</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
