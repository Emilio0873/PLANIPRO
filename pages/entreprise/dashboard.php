<?php
require_once __DIR__ . '/../../includes/init.php';
$user = require_role('entreprise');
$entrepriseId = get_entreprise_id_for_user($user);
$pdo = db();

$stats = [
    'rdv_total' => 0,
    'rdv_aujourdhui' => 0,
    'rdv_semaine' => 0,
    'act_planifiees' => 0,
    'act_terminees' => 0,
    'clients' => 0,
    'partenaires' => 0,
];

$rdvs = [];
$priorites = [];
$chartLabels = [];
$chartValues = [];

if ($entrepriseId) {
    $q = $pdo->prepare('SELECT COUNT(*) FROM rendez_vous WHERE entreprise_id = ?');
    $q->execute([$entrepriseId]);
    $stats['rdv_total'] = (int) $q->fetchColumn();

    $q = $pdo->prepare("SELECT COUNT(*) FROM rendez_vous WHERE entreprise_id = ? AND DATE(date_debut) = CURDATE()");
    $q->execute([$entrepriseId]);
    $stats['rdv_aujourdhui'] = (int) $q->fetchColumn();

    $q = $pdo->prepare("SELECT COUNT(*) FROM rendez_vous WHERE entreprise_id = ? AND YEARWEEK(date_debut, 1) = YEARWEEK(CURDATE(), 1)");
    $q->execute([$entrepriseId]);
    $stats['rdv_semaine'] = (int) $q->fetchColumn();

    $q = $pdo->prepare("SELECT COUNT(*) FROM activites WHERE entreprise_id = ? AND statut IN ('a_faire','en_cours')");
    $q->execute([$entrepriseId]);
    $stats['act_planifiees'] = (int) $q->fetchColumn();

    $q = $pdo->prepare("SELECT COUNT(*) FROM activites WHERE entreprise_id = ? AND statut = 'terminee'");
    $q->execute([$entrepriseId]);
    $stats['act_terminees'] = (int) $q->fetchColumn();

    $q = $pdo->prepare("SELECT COUNT(*) FROM users WHERE entreprise_id = ? AND role = 'client' AND actif = 1");
    $q->execute([$entrepriseId]);
    $stats['clients'] = (int) $q->fetchColumn();

    $q = $pdo->prepare("SELECT COUNT(*) FROM users WHERE entreprise_id = ? AND role = 'partenaire' AND actif = 1");
    $q->execute([$entrepriseId]);
    $stats['partenaires'] = (int) $q->fetchColumn();

    $recent = $pdo->prepare("SELECT r.*, u.prenom, u.nom FROM rendez_vous r JOIN users u ON u.id = r.demandeur_id WHERE r.entreprise_id = ? AND r.statut IN ('en_attente','accepte','reporte') AND r.date_debut >= NOW() ORDER BY r.date_debut ASC LIMIT 6");
    $recent->execute([$entrepriseId]);
    $rdvs = $recent->fetchAll();

    $prio = $pdo->prepare("SELECT a.*, u.prenom, u.nom FROM activites a LEFT JOIN users u ON u.id = a.assigne_a WHERE a.entreprise_id = ? AND a.statut IN ('a_faire','en_cours') ORDER BY FIELD(a.priorite,'urgente','haute','moyenne','basse'), a.echeance ASC LIMIT 5");
    $prio->execute([$entrepriseId]);
    $priorites = $prio->fetchAll();

    $chart = $pdo->prepare("SELECT statut, COUNT(*) c FROM rendez_vous WHERE entreprise_id = ? GROUP BY statut");
    $chart->execute([$entrepriseId]);
    foreach ($chart->fetchAll() as $row) {
        $chartLabels[] = statut_label($row['statut']);
        $chartValues[] = (int) $row['c'];
    }
}

$pageTitle = 'Tableau de bord';
$extraScripts = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
  const el = document.getElementById("rdvChart");
  if (!el || typeof Chart === "undefined") return;
  new Chart(el, {
    type: "doughnut",
    data: {
      labels: ' . json_encode($chartLabels, JSON_UNESCAPED_UNICODE) . ',
      datasets: [{
        data: ' . json_encode($chartValues) . ',
        backgroundColor: ["#F59E0B","#22C55E","#EF4444","#3B82F6","#64748b","#38BDF8"],
        borderWidth: 0
      }]
    },
    options: {
      plugins: {
        legend: {
          position: "bottom",
          labels: { boxWidth: 10, color: "#9fb0cc", font: { family: "Poppins", size: 11 } }
        }
      },
      cutout: "68%"
    }
  });
});
</script>';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="dash-page">
    <section class="dash-welcome">
        <div class="dash-welcome__text">
            <p class="dash-welcome__eyebrow">Vue d’ensemble</p>
            <h2>Bonjour, <?= e($user['prenom']) ?></h2>
            <p>Suivez vos rendez-vous, activités et contacts depuis un seul endroit.</p>
        </div>
        <div class="dash-welcome__actions">
            <a class="btn btn-primary" href="<?= url('pages/entreprise/rendez_vous_ajouter.php') ?>">Ajouter un RDV</a>
            <a class="btn btn-secondary" href="<?= url('pages/entreprise/activite_ajouter.php') ?>">Ajouter une activité</a>
            <a class="btn btn-secondary" href="<?= url('pages/entreprise/calendrier.php') ?>">Calendrier</a>
        </div>
    </section>

    <section class="kpi-grid">
        <article class="kpi-card kpi-card--accent">
            <span class="kpi-card__label">Total RDV</span>
            <strong class="kpi-card__value"><?= $stats['rdv_total'] ?></strong>
            <span class="kpi-card__hint">Tous statuts</span>
        </article>
        <article class="kpi-card">
            <span class="kpi-card__label">Aujourd’hui</span>
            <strong class="kpi-card__value"><?= $stats['rdv_aujourdhui'] ?></strong>
            <span class="kpi-card__hint">Rendez-vous du jour</span>
        </article>
        <article class="kpi-card">
            <span class="kpi-card__label">Cette semaine</span>
            <strong class="kpi-card__value"><?= $stats['rdv_semaine'] ?></strong>
            <span class="kpi-card__hint">Planifiés</span>
        </article>
        <article class="kpi-card">
            <span class="kpi-card__label">Activités ouvertes</span>
            <strong class="kpi-card__value"><?= $stats['act_planifiees'] ?></strong>
            <span class="kpi-card__hint"><?= (int) $stats['act_terminees'] ?> terminées</span>
        </article>
    </section>

    <section class="kpi-grid kpi-grid--3">
        <article class="kpi-card kpi-card--compact">
            <span class="kpi-card__label">Clients</span>
            <strong class="kpi-card__value"><?= $stats['clients'] ?></strong>
        </article>
        <article class="kpi-card kpi-card--compact">
            <span class="kpi-card__label">Partenaires</span>
            <strong class="kpi-card__value"><?= $stats['partenaires'] ?></strong>
        </article>
        <article class="kpi-card kpi-card--compact">
            <span class="kpi-card__label">Activités terminées</span>
            <strong class="kpi-card__value"><?= $stats['act_terminees'] ?></strong>
        </article>
    </section>

    <section class="dash-layout">
        <div class="card dash-panel">
            <div class="card-header">
                <h2>Rendez-vous à venir</h2>
                <a class="btn btn-sm btn-secondary" href="<?= url('pages/entreprise/rendez_vous.php') ?>">Voir tout</a>
            </div>
            <?php if (!$rdvs): ?>
                <div class="empty">Aucun rendez-vous à venir.</div>
            <?php else: ?>
                <div class="people-list">
                    <?php foreach ($rdvs as $r): ?>
                        <?php
                        $debut = new DateTime($r['date_debut']);
                        $initials = user_initials(['prenom' => $r['prenom'], 'nom' => $r['nom']]);
                        ?>
                        <article class="people-card">
                            <div class="people-card__main">
                                <div class="person-avatar" aria-hidden="true"><?= e($initials) ?></div>
                                <div class="people-card__info">
                                    <div class="people-card__title">
                                        <strong><?= e($r['titre']) ?></strong>
                                        <?= statut_badge($r['statut']) ?>
                                    </div>
                                    <div class="people-card__meta">
                                        <span><?= e($r['prenom'] . ' ' . $r['nom']) ?></span>
                                        <span class="dot-sep">·</span>
                                        <span><?= e($debut->format('d/m/Y')) ?></span>
                                        <span class="dot-sep">·</span>
                                        <span><?= e($debut->format('H:i')) ?></span>
                                        <span class="dot-sep">·</span>
                                        <span><?= e(ucfirst($r['type_demandeur'])) ?></span>
                                    </div>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <aside class="dash-side">
            <div class="card dash-panel">
                <div class="card-header">
                    <h2>Répartition RDV</h2>
                </div>
                <?php if (!$chartValues): ?>
                    <div class="empty">Pas encore de données.</div>
                <?php else: ?>
                    <div class="chart-wrap chart-wrap--dash"><canvas id="rdvChart"></canvas></div>
                <?php endif; ?>
            </div>
            <div class="card dash-panel dash-panel--cta">
                <div class="card-header">
                    <h2>Planning</h2>
                </div>
                <p class="help-text help-text--flush">Ouvrez le calendrier pour visualiser rendez-vous et activités.</p>
                <a class="btn btn-primary btn-block" href="<?= url('pages/entreprise/calendrier.php') ?>">Ouvrir le calendrier</a>
            </div>
        </aside>
    </section>

    <section class="card dash-panel">
        <div class="card-header">
            <h2>Activités prioritaires</h2>
            <a class="btn btn-sm btn-secondary" href="<?= url('pages/entreprise/activites.php') ?>">Voir tout</a>
        </div>
        <?php if (!$priorites): ?>
            <div class="empty">Aucune activité ouverte.</div>
        <?php else: ?>
            <div class="people-list">
                <?php foreach ($priorites as $a): ?>
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
                                    <span><?= e($a['prenom'] ? $a['prenom'] . ' ' . $a['nom'] : 'Non assigné') ?></span>
                                    <span class="dot-sep">·</span>
                                    <span><?= e(format_date($a['echeance'])) ?></span>
                                </div>
                                <div class="mt-xs" style="max-width:220px"><?= progression_bar_html((int) ($a['progression'] ?? 0), 'sm') ?></div>
                            </div>
                        </div>
                        <div class="people-card__actions">
                            <a class="btn btn-sm btn-secondary" href="<?= url('pages/entreprise/activite_ajouter.php?edit=' . (int) $a['id']) ?>">Modifier</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
