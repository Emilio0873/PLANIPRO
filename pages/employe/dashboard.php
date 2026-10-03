<?php
require_once __DIR__ . '/../../includes/init.php';
$user = require_role('employe');
$entrepriseId = get_entreprise_id_for_user($user);
$pdo = db();

$stActs = $pdo->prepare("SELECT COUNT(*) FROM activites WHERE assigne_a = ? AND statut IN ('a_faire','en_cours')");
$stActs->execute([$user['id']]);
$nbActs = (int) $stActs->fetchColumn();
$rdvCount = 0;
if ($entrepriseId) {
    $q = $pdo->prepare("SELECT COUNT(*) FROM rendez_vous WHERE entreprise_id = ? AND statut = 'accepte' AND date_debut >= NOW()");
    $q->execute([$entrepriseId]);
    $rdvCount = (int) $q->fetchColumn();
}
$mesActs = $pdo->prepare("SELECT * FROM activites WHERE assigne_a = ? ORDER BY FIELD(priorite,'urgente','haute','moyenne','basse'), echeance ASC LIMIT 5");
$mesActs->execute([$user['id']]);
$rows = $mesActs->fetchAll();

$pageTitle = 'Tableau de bord';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="dash-page">
    <section class="dash-welcome">
        <div class="dash-welcome__text">
            <p class="dash-welcome__eyebrow">Espace visiteur</p>
            <h2>Bonjour, <?= e($user['prenom']) ?></h2>
            <p>Retrouvez vos activités et les rendez-vous de l’entreprise.</p>
        </div>
        <div class="dash-welcome__actions">
            <a class="btn btn-primary" href="<?= url('pages/employe/activites.php') ?>">Mes activités</a>
            <a class="btn btn-secondary" href="<?= url('pages/employe/calendrier.php') ?>">Calendrier</a>
        </div>
    </section>

    <section class="kpi-grid kpi-grid--3" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
        <article class="kpi-card kpi-card--accent">
            <span class="kpi-card__label">Activités ouvertes</span>
            <strong class="kpi-card__value"><?= $nbActs ?></strong>
            <span class="kpi-card__hint">À faire / en cours</span>
        </article>
        <article class="kpi-card">
            <span class="kpi-card__label">RDV à venir</span>
            <strong class="kpi-card__value"><?= $rdvCount ?></strong>
            <span class="kpi-card__hint">Confirmés</span>
        </article>
    </section>

    <section class="card dash-panel">
        <div class="card-header">
            <h2>Activités prioritaires</h2>
            <a class="btn btn-sm btn-secondary" href="<?= url('pages/employe/activites.php') ?>">Voir tout</a>
        </div>
        <?php if (!$rows): ?>
            <div class="empty">Aucune activité assignée.</div>
        <?php else: ?>
            <div class="people-list">
                <?php foreach ($rows as $a): ?>
                    <article class="people-card">
                        <div class="people-card__main">
                            <div class="person-avatar" aria-hidden="true"><?= e(initials_from_text($a['titre'])) ?></div>
                            <div class="people-card__info">
                                <div class="people-card__title">
                                    <strong><?= e($a['titre']) ?></strong>
                                    <?= statut_badge($a['priorite']) ?>
                                    <?= statut_badge($a['statut']) ?>
                                </div>
                                <div class="people-card__meta">
                                    <span>Échéance : <?= e(format_date($a['echeance'])) ?></span>
                                </div>
                                <div class="mt-xs" style="max-width:220px"><?= progression_bar_html((int) ($a['progression'] ?? 0), 'sm') ?></div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
