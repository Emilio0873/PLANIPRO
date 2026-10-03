<?php
require_once __DIR__ . '/../../includes/init.php';
$user = require_role('client');
$pdo = db();
$stAttente = $pdo->prepare("SELECT COUNT(*) FROM rendez_vous WHERE demandeur_id = ? AND statut = 'en_attente'");
$stAttente->execute([$user['id']]);
$nbAttente = (int) $stAttente->fetchColumn();
$stAcceptes = $pdo->prepare("SELECT COUNT(*) FROM rendez_vous WHERE demandeur_id = ? AND statut = 'accepte'");
$stAcceptes->execute([$user['id']]);
$nbAcceptes = (int) $stAcceptes->fetchColumn();
$prochains = $pdo->prepare("SELECT r.*, e.raison_sociale FROM rendez_vous r JOIN entreprises e ON e.id = r.entreprise_id WHERE r.demandeur_id = ? AND r.statut IN ('en_attente','accepte','reporte') AND r.date_debut >= NOW() ORDER BY r.date_debut ASC LIMIT 5");
$prochains->execute([$user['id']]);
$liste = $prochains->fetchAll();
$pageTitle = 'Tableau de bord';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="dash-page">
    <section class="dash-welcome">
        <div class="dash-welcome__text">
            <p class="dash-welcome__eyebrow">Espace client</p>
            <h2>Bonjour, <?= e($user['prenom']) ?></h2>
            <p>Suivez vos demandes et rendez-vous confirmés.</p>
        </div>
        <div class="dash-welcome__actions">
            <a class="btn btn-primary" href="<?= url('pages/client/entreprises.php') ?>">Demander un RDV</a>
            <a class="btn btn-secondary" href="<?= url('pages/client/rendez_vous.php') ?>">Mes rendez-vous</a>
        </div>
    </section>

    <section class="kpi-grid kpi-grid--3" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
        <article class="kpi-card">
            <span class="kpi-card__label">En attente</span>
            <strong class="kpi-card__value"><?= $nbAttente ?></strong>
        </article>
        <article class="kpi-card kpi-card--accent">
            <span class="kpi-card__label">Acceptés</span>
            <strong class="kpi-card__value"><?= $nbAcceptes ?></strong>
        </article>
    </section>

    <section class="card dash-panel">
        <div class="card-header">
            <h2>Prochains rendez-vous</h2>
            <a class="btn btn-sm btn-secondary" href="<?= url('pages/client/rendez_vous.php') ?>">Voir tout</a>
        </div>
        <?php if (!$liste): ?>
            <div class="empty">Aucun rendez-vous à venir.</div>
        <?php else: ?>
            <div class="people-list">
                <?php foreach ($liste as $r): ?>
                    <article class="people-card">
                        <div class="people-card__main">
                            <div class="person-avatar" aria-hidden="true"><?= e(initials_from_text($r['titre'])) ?></div>
                            <div class="people-card__info">
                                <div class="people-card__title">
                                    <strong><?= e($r['titre']) ?></strong>
                                    <?= statut_badge($r['statut']) ?>
                                </div>
                                <div class="people-card__meta">
                                    <span><?= e($r['raison_sociale']) ?></span>
                                    <span class="dot-sep">·</span>
                                    <span><?= e(format_datetime($r['date_debut'])) ?></span>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
