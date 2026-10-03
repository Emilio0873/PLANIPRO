<?php
require_once __DIR__ . '/../../includes/init.php';
$user = require_role('entreprise');
$entrepriseId = get_entreprise_id_for_user($user);

$stmt = db()->prepare("SELECT * FROM users WHERE entreprise_id = ? AND role = 'employe' ORDER BY nom, prenom");
$stmt->execute([$entrepriseId]);
$employes = $stmt->fetchAll();

$pageTitle = 'Visiteurs';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-stack">
    <div class="card">
        <div class="card-header">
            <h2>Liste des visiteurs</h2>
            <div class="toolbar-actions">
                <span class="count-pill"><?= count($employes) ?></span>
                <a class="btn btn-sm btn-primary" href="<?= url('pages/entreprise/visiteur_ajouter.php') ?>">Ajouter</a>
            </div>
        </div>

        <?php if (!$employes): ?>
            <div class="empty">Aucun visiteur enregistré.</div>
        <?php else: ?>
            <div class="people-list">
                <?php foreach ($employes as $emp): ?>
                    <?php
                    $fromPublic = contact_public_by_visiteur((int) $entrepriseId, (int) $emp['id']);
                    $initials = user_initials(['prenom' => $emp['prenom'], 'nom' => $emp['nom']]);
                    ?>
                    <article class="people-card">
                        <div class="people-card__main">
                            <div class="person-avatar" aria-hidden="true"><?= e($initials) ?></div>
                            <div class="people-card__info">
                                <div class="people-card__title">
                                    <strong><?= e($emp['prenom'] . ' ' . $emp['nom']) ?></strong>
                                    <?= $emp['actif'] ? statut_badge('accepte') : statut_badge('annule') ?>
                                </div>
                                <div class="people-card__meta">
                                    <span><?= e($emp['email']) ?></span>
                                    <?php if (!empty($emp['telephone'])): ?>
                                        <span class="dot-sep">·</span>
                                        <span><?= e($emp['telephone']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($fromPublic): ?>
                                    <span class="tag-soft">Message public</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="people-card__actions">
                            <a class="btn btn-sm btn-primary" href="<?= url('pages/messagerie/index.php?nouveau=1&to=' . (int) $emp['id']) ?>">Messagerie</a>
                            <a class="btn btn-sm btn-secondary" href="<?= url('pages/entreprise/employe_edit.php?id=' . $emp['id']) ?>">Modifier</a>
                            <?php if ($fromPublic): ?>
                                <a class="btn btn-sm btn-secondary" href="<?= url('pages/entreprise/visiteur_notifier.php?contact_id=' . (int) $fromPublic['id']) ?>">Notifier</a>
                            <?php endif; ?>
                            <form method="post" action="<?= url('actions/employe_toggle.php') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $emp['id'] ?>">
                                <button class="btn btn-sm <?= $emp['actif'] ? 'btn-ghost' : 'btn-success' ?>" type="submit" data-confirm="Confirmer ?">
                                    <?= $emp['actif'] ? 'Désactiver' : 'Activer' ?>
                                </button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
