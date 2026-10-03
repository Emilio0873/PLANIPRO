<?php
require_once __DIR__ . '/../../includes/init.php';
$user = require_role('entreprise');
$entrepriseId = get_entreprise_id_for_user($user);
if (!$entrepriseId) {
    flash('error', 'Entreprise introuvable.');
    redirect(role_home('entreprise'));
}

$contactId = (int) get('contact_id', 0);
$stmt = db()->prepare('SELECT * FROM contact_public WHERE id = ? AND entreprise_id = ?');
$stmt->execute([$contactId, $entrepriseId]);
$contact = $stmt->fetch();
if (!$contact) {
    flash('error', 'Contact public introuvable.');
    redirect('pages/entreprise/employes.php');
}

$visiteur = !empty($contact['expediteur_user_id'])
    ? get_user_by_id((int) $contact['expediteur_user_id'])
    : null;
if (!$visiteur) {
    flash('error', 'Créez d’abord le compte visiteur.');
    redirect('pages/entreprise/visiteur_creer.php?contact_id=' . $contactId);
}

$rdv = rdv_status_for_contact_email((int) $entrepriseId, (string) $contact['email']);
$needPassword = empty($contact['mdp_a_envoyer']);

$pageTitle = 'Notifier le visiteur';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="card form-card">
    <div class="card-header">
        <h2>Envoyer la notification</h2>
        <a class="btn btn-sm btn-secondary" href="<?= url('pages/entreprise/employes.php') ?>">Retour</a>
    </div>

    <div class="person-summary">
        <div class="person-avatar" aria-hidden="true"><?= e(user_initials(['prenom' => $visiteur['prenom'], 'nom' => $visiteur['nom']])) ?></div>
        <div>
            <strong><?= e($visiteur['prenom'] . ' ' . $visiteur['nom']) ?></strong>
            <div class="help-text"><?= e($visiteur['email']) ?></div>
            <?php if ($rdv): ?>
                <div class="mt-xs">RDV : <?= statut_badge($rdv['statut']) ?> — <?= e($rdv['titre']) ?></div>
            <?php else: ?>
                <div class="help-text mt-xs">Aucun rendez-vous trouvé pour cet email.</div>
            <?php endif; ?>
        </div>
    </div>

    <form method="post" action="<?= url('actions/contact_envoyer_notif.php') ?>" class="settings-form">
        <?= csrf_field() ?>
        <input type="hidden" name="contact_id" value="<?= (int) $contact['id'] ?>">
        <input type="hidden" name="user_id" value="<?= (int) $visiteur['id'] ?>">
        <?php if ($needPassword): ?>
            <div class="form-group">
                <label for="password">Mot de passe à communiquer</label>
                <input type="password" id="password" name="password" required minlength="6" placeholder="Celui défini pour le visiteur">
                <p class="help-text">Requis pour renvoyer les identifiants (le mot de passe n’est plus stocké après le premier envoi).</p>
            </div>
        <?php else: ?>
            <p class="help-text">Le mot de passe défini à la création sera inclus dans la notification.</p>
        <?php endif; ?>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Envoyer la notification</button>
            <a class="btn btn-secondary" href="<?= url('pages/entreprise/messages_recus.php') ?>">Messages publics</a>
        </div>
    </form>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
