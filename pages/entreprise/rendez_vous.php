<?php
require_once __DIR__ . '/../../includes/init.php';
$user = require_role('entreprise');
$entrepriseId = get_entreprise_id_for_user($user);
$filtre = get('statut', '');
$q = get('q', '');

$sql = "SELECT r.*, u.prenom, u.nom, u.email FROM rendez_vous r JOIN users u ON u.id = r.demandeur_id WHERE r.entreprise_id = ?";
$params = [$entrepriseId];
if ($filtre !== '') {
    $sql .= ' AND r.statut = ?';
    $params[] = $filtre;
}
if ($q !== '') {
    $sql .= ' AND (r.titre LIKE ? OR u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like);
}
$sql .= ' ORDER BY r.date_debut DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$rdvs = $stmt->fetchAll();

$pageTitle = 'Rendez-vous';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-stack">
<div class="card">
    <div class="card-header">
        <h2>Liste des rendez-vous</h2>
        <div class="toolbar-actions">
            <span class="count-pill"><?= count($rdvs) ?></span>
            <a class="btn btn-sm btn-secondary" href="<?= url('pages/entreprise/calendrier.php') ?>">Calendrier</a>
            <a class="btn btn-sm btn-primary" href="<?= url('pages/entreprise/rendez_vous_ajouter.php') ?>">Ajouter</a>
        </div>
    </div>
    <form class="search-bar" method="get">
        <div class="form-group">
            <label for="q">Recherche</label>
            <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Nom, titre, email…">
        </div>
        <div class="form-group">
            <label for="statut">Statut</label>
            <select name="statut" id="statut">
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
                <?php
                $initials = user_initials(['prenom' => $r['prenom'], 'nom' => $r['nom']]);
                $debut = new DateTime($r['date_debut']);
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
                                <span><?= e($debut->format('d/m/Y H:i')) ?></span>
                                <span class="dot-sep">·</span>
                                <span><?= e($r['type_demandeur'] === 'employe' ? 'Visiteur' : ucfirst($r['type_demandeur'])) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="people-card__actions">
                        <a class="btn btn-sm btn-primary" href="<?= url('pages/messagerie/index.php?nouveau=1&to=' . (int) $r['demandeur_id']) ?>">Messagerie</a>
                        <?php if (in_array($r['statut'], ['en_attente', 'reporte'], true)): ?>
                            <form method="post" action="<?= url('actions/rdv_action.php') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                <input type="hidden" name="action" value="accepter">
                                <button class="btn btn-sm btn-success" type="submit">Confirmer</button>
                            </form>
                        <?php endif; ?>
                        <?php if ($r['statut'] === 'en_attente'): ?>
                            <form method="post" action="<?= url('actions/rdv_action.php') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                <input type="hidden" name="action" value="refuser">
                                <button class="btn btn-sm btn-danger" type="submit" data-confirm="Refuser ?">Refuser</button>
                            </form>
                        <?php endif; ?>
                        <?php if (in_array($r['statut'], ['en_attente', 'accepte', 'reporte'], true)): ?>
                            <form method="post" action="<?= url('actions/rdv_action.php') ?>" class="rdv-report-form">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                <input type="hidden" name="action" value="reporter">
                                <input type="datetime-local" name="date_debut" required
                                       value="<?= e((new DateTime($r['date_debut']))->format('Y-m-d\TH:i')) ?>"
                                       title="Nouvelle date de début">
                                <input type="datetime-local" name="date_fin" required
                                       value="<?= e((new DateTime($r['date_fin']))->format('Y-m-d\TH:i')) ?>"
                                       title="Nouvelle date de fin">
                                <button class="btn btn-sm btn-secondary" type="submit">Reporter</button>
                            </form>
                            <form method="post" action="<?= url('actions/rdv_action.php') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                <input type="hidden" name="action" value="annuler">
                                <button class="btn btn-sm btn-ghost" type="submit" data-confirm="Annuler ?">Annuler</button>
                            </form>
                        <?php endif; ?>
                        <?php if ($r['statut'] === 'accepte'): ?>
                            <form method="post" action="<?= url('actions/rdv_action.php') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                <input type="hidden" name="action" value="terminer">
                                <button class="btn btn-sm btn-secondary" type="submit">Terminer</button>
                            </form>
                            <form method="post" action="<?= url('actions/rdv_creer_espace.php') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="rdv_id" value="<?= (int) $r['id'] ?>">
                                <button class="btn btn-sm btn-secondary" type="submit">Compte visiteur</button>
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
