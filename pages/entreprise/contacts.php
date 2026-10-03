<?php
require_once __DIR__ . '/../../includes/init.php';
$user = require_role('entreprise');
$entrepriseId = get_entreprise_id_for_user($user);

$stmt = db()->prepare("
    SELECT u.*,
           c.entreprise_nom, c.adresse AS client_adresse,
           p.organisation, p.type_partenariat, p.adresse AS partenaire_adresse
    FROM users u
    LEFT JOIN clients c ON c.user_id = u.id
    LEFT JOIN partenaires p ON p.user_id = u.id
    WHERE u.entreprise_id = ? AND u.role IN ('client', 'partenaire')
    ORDER BY u.role, u.nom, u.prenom
");
$stmt->execute([$entrepriseId]);
$contacts = $stmt->fetchAll();

$pageTitle = 'Contacts';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-stack">
    <div class="card">
        <div class="card-header">
            <h2>Liste des contacts</h2>
            <div class="toolbar-actions">
                <span class="count-pill"><?= count($contacts) ?></span>
                <a class="btn btn-sm btn-primary" href="<?= url('pages/entreprise/contact_ajouter.php') ?>">Ajouter</a>
            </div>
        </div>
        <?php if (!$contacts): ?>
            <div class="empty">Aucun contact.</div>
        <?php else: ?>
            <div class="people-list">
                <?php foreach ($contacts as $c): ?>
                    <?php $initials = user_initials(['prenom' => $c['prenom'], 'nom' => $c['nom']]); ?>
                    <article class="people-card">
                        <div class="people-card__main">
                            <div class="person-avatar" aria-hidden="true"><?= e($initials) ?></div>
                            <div class="people-card__info">
                                <div class="people-card__title">
                                    <strong><?= e($c['prenom'] . ' ' . $c['nom']) ?></strong>
                                    <span class="tag-soft"><?= e(ucfirst($c['role'])) ?></span>
                                    <?= $c['actif'] ? statut_badge('accepte') : statut_badge('annule') ?>
                                </div>
                                <div class="people-card__meta">
                                    <span><?= e($c['email']) ?></span>
                                    <?php if (!empty($c['telephone'])): ?>
                                        <span class="dot-sep">·</span>
                                        <span><?= e($c['telephone']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="people-card__actions">
                            <form method="post" action="<?= url('actions/contact_toggle.php') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                <input type="hidden" name="type" value="<?= e($c['role']) ?>">
                                <button class="btn btn-sm <?= $c['actif'] ? 'btn-ghost' : 'btn-success' ?>" type="submit" data-confirm="Confirmer ?">
                                    <?= $c['actif'] ? 'Désactiver' : 'Activer' ?>
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
