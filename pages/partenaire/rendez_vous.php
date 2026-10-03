<?php
require_once __DIR__ . '/../../includes/init.php';
$user = require_role('partenaire');
$filtre = get('statut', '');

$sql = "SELECT r.*, e.raison_sociale FROM rendez_vous r JOIN entreprises e ON e.id = r.entreprise_id WHERE r.demandeur_id = ?";
$params = [$user['id']];
if ($filtre !== '') {
    $sql .= ' AND r.statut = ?';
    $params[] = $filtre;
}
$sql .= ' ORDER BY r.date_debut DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$rdvs = $stmt->fetchAll();

$pageTitle = 'Mes rendez-vous';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-stack">
<div class="card">
    <div class="card-header">
        <h2>Liste des rendez-vous</h2>
        <div class="toolbar-actions">
            <span class="count-pill"><?= count($rdvs) ?></span>
            <a class="btn btn-sm btn-primary" href="<?= url('pages/partenaire/entreprises.php') ?>">Nouvelle demande</a>
        </div>
    </div>
    <form class="filters filters--simple" method="get">
        <div class="form-group">
            <label for="statut">Statut</label>
            <select name="statut" id="statut" onchange="this.form.submit()">
                <option value="">Tous les statuts</option>
                <?php foreach (['en_attente','accepte','refuse','reporte','annule','termine'] as $s): ?>
                    <option value="<?= $s ?>" <?= $filtre === $s ? 'selected' : '' ?>><?= e(statut_label($s)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-primary" type="submit">Filtrer</button>
    </form>
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
                                <span><?= e($r['raison_sociale']) ?></span>
                                <span class="dot-sep">·</span>
                                <span><?= e(format_datetime($r['date_debut'])) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="people-card__actions">
                        <?php if ($r['statut'] === 'en_attente'): ?>
                            <a class="btn btn-sm btn-secondary" href="<?= url('pages/partenaire/rdv_demande.php?id=' . $r['id']) ?>">Modifier</a>
                        <?php endif; ?>
                        <?php if (in_array($r['statut'], ['en_attente','accepte','reporte'], true)): ?>
                            <form method="post" action="<?= url('actions/rdv_action.php') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                <input type="hidden" name="action" value="annuler">
                                <button class="btn btn-sm btn-danger" type="submit" data-confirm="Annuler cette demande ?">Annuler</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
