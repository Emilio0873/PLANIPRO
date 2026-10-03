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
    flash('error', 'Message introuvable.');
    redirect('pages/entreprise/messages_recus.php');
}

$parts = preg_split('/\s+/', trim((string) $contact['nom']), 2) ?: [trim((string) $contact['nom'])];
$prenomDef = $parts[0] ?? '';
$nomDef = $parts[1] ?? '';

$pageTitle = 'Créer un compte visiteur';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="card form-card">
    <div class="card-header">
        <h2>Créer un compte visiteur</h2>
        <a class="btn btn-sm btn-secondary" href="<?= url('pages/entreprise/messages_recus.php') ?>">Retour</a>
    </div>
    <p class="help-text">Définissez le mot de passe du visiteur. Ensuite, envoyez-lui une notification avec ses coordonnées de connexion.</p>
    <form method="post" action="<?= url('actions/contact_creer_visiteur.php') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="contact_id" value="<?= (int) $contact['id'] ?>">
        <div class="form-row">
            <div class="form-group">
                <label for="prenom">Prénom</label>
                <input type="text" id="prenom" name="prenom" value="<?= e($prenomDef) ?>" required>
            </div>
            <div class="form-group">
                <label for="nom">Nom</label>
                <input type="text" id="nom" name="nom" value="<?= e($nomDef) ?>" required>
            </div>
        </div>
        <div class="form-group">
            <label for="email">Email (login)</label>
            <input type="email" id="email" name="email" value="<?= e($contact['email']) ?>" required>
        </div>
        <div class="form-group">
            <label for="telephone">Téléphone</label>
            <input type="tel" id="telephone" name="telephone" value="<?= e($contact['telephone'] ?? '') ?>">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="password">Mot de passe du visiteur</label>
                <input type="password" id="password" name="password" required minlength="6">
            </div>
            <div class="form-group">
                <label for="password_confirm">Confirmation</label>
                <input type="password" id="password_confirm" name="password_confirm" required minlength="6">
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Créer le compte</button>
            <a class="btn btn-secondary" href="<?= url('pages/entreprise/messages_recus.php') ?>">Annuler</a>
        </div>
    </form>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
