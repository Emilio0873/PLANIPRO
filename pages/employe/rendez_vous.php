<?php
require_once __DIR__ . '/../../includes/init.php';
$user = require_role('employe');
$entrepriseId = get_entreprise_id_for_user($user);

$stmt = db()->prepare("
    SELECT r.*, u.prenom, u.nom
    FROM rendez_vous r
    JOIN users u ON u.id = r.demandeur_id
    WHERE r.entreprise_id = ? AND r.demandeur_id = ?
      AND r.statut IN ('accepte','reporte','en_attente')
    ORDER BY r.date_debut ASC
");
$stmt->execute([$entrepriseId, (int) $user['id']]);
$rdvs = $stmt->fetchAll();

$ownerId = 0;
if ($entrepriseId) {
    $own = db()->prepare('SELECT user_id FROM entreprises WHERE id = ?');
    $own->execute([$entrepriseId]);
    $ownerId = (int) $own->fetchColumn();
}

$pageTitle = 'Rendez-vous';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="card">
    <div class="card-header">
        <h2>Mes rendez-vous</h2>
        <span class="count-pill"><?= count($rdvs) ?></span>
    </div>
    <?php if (!$rdvs): ?>
        <div class="empty">Aucun rendez-vous.</div>
    <?php else: ?>
        <div class="people-list">
            <?php foreach ($rdvs as $r): ?>
                <article class="people-card">
                    <div class="people-card__main">
                        <div class="person-avatar" aria-hidden="true"><?= e(initials_from_text($r['titre'])) ?></div>
                        <div class="people-card__info">
                            <div class="people-card__title">
                                <strong><?= e($r['titre']) ?></strong>
                                <?= statut_badge($r['statut']) ?>
                            </div>
                            <div class="people-card__meta">
                                <span><?= e(format_datetime($r['date_debut'])) ?></span>
                                <?php if (!empty($r['lieu'])): ?>
                                    <span class="dot-sep">·</span>
                                    <span><?= e($r['lieu']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="people-card__actions">
                        <?php if ($ownerId > 0): ?>
                            <a class="btn btn-sm btn-primary" href="<?= url('pages/messagerie/index.php?nouveau=1&to=' . $ownerId) ?>">Messagerie</a>
                        <?php endif; ?>
                        <?php if (in_array($r['statut'], ['en_attente', 'accepte', 'reporte'], true)): ?>
                            <form method="post" action="<?= url('actions/rdv_action.php') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                <input type="hidden" name="action" value="annuler">
                                <button class="btn btn-sm btn-danger" type="submit" data-confirm="Annuler ce rendez-vous ?">Annuler</button>
                            </form>
                        <?php endif; ?>
                        <a class="btn btn-sm btn-secondary" href="<?= url('pages/employe/calendrier.php') ?>">Calendrier</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
