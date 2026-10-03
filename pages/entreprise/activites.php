<?php
require_once __DIR__ . '/../../includes/init.php';
$user = require_role('entreprise');
$entrepriseId = get_entreprise_id_for_user($user);

$stmt = db()->prepare("SELECT a.*, u.prenom, u.nom FROM activites a LEFT JOIN users u ON u.id = a.assigne_a WHERE a.entreprise_id = ? AND a.statut != 'annulee' ORDER BY FIELD(a.priorite,'urgente','haute','moyenne','basse'), a.echeance IS NULL, a.echeance ASC");
$stmt->execute([$entrepriseId]);
$activites = $stmt->fetchAll();

$avgProg = 0;
if ($activites) {
    $sum = 0;
    foreach ($activites as $a) {
        $sum += (int) ($a['progression'] ?? 0);
    }
    $avgProg = (int) round($sum / count($activites));
}

$pageTitle = 'Activités';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-stack">
<div class="card">
    <div class="card-header">
        <div>
            <h2>Activités</h2>
            <p class="help-text help-text--flush">Suivez les tâches de votre équipe et leur avancement.</p>
        </div>
        <div class="toolbar-actions">
            <?php if ($activites): ?>
                <div class="act-avg">
                    <span>Progression moyenne</span>
                    <?= progression_bar_html($avgProg, 'sm') ?>
                </div>
            <?php else: ?>
                <span class="count-pill">0</span>
            <?php endif; ?>
            <a class="btn btn-sm btn-primary" href="<?= url('pages/entreprise/activite_ajouter.php') ?>">Nouvelle activité</a>
        </div>
    </div>
    <?php if (!$activites): ?>
        <div class="empty">Aucune activité pour le moment. Créez-en une pour démarrer le suivi.</div>
    <?php else: ?>
        <div class="people-list">
            <?php foreach ($activites as $a): ?>
                <article class="people-card">
                    <div class="people-card__main">
                        <div class="person-avatar" aria-hidden="true"><?= e(initials_from_text($a['titre'])) ?></div>
                        <div class="people-card__info">
                            <div class="people-card__title">
                                <strong><?= e($a['titre']) ?></strong>
                                <?= statut_badge($a['statut']) ?>
                                <?= statut_badge($a['priorite']) ?>
                            </div>
                            <div class="people-card__meta">
                                <span><?= e($a['prenom'] ? $a['prenom'].' '.$a['nom'] : 'Non assigné') ?></span>
                                <span class="dot-sep">·</span>
                                <span><?= e(format_date($a['echeance'])) ?></span>
                            </div>
                            <div class="mt-xs" style="max-width:220px"><?= progression_bar_html((int) ($a['progression'] ?? 0), 'sm') ?></div>
                        </div>
                    </div>
                    <div class="people-card__actions">
                        <a class="btn btn-sm btn-secondary" href="<?= url('pages/entreprise/activite_ajouter.php?edit=' . (int) $a['id']) ?>">Modifier</a>
                        <form method="post" action="<?= url('actions/activite_delete.php') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                            <button class="btn btn-sm btn-ghost" type="submit" data-confirm="Supprimer cette activité ?">Supprimer</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
