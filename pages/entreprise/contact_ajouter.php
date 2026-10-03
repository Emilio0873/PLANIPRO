<?php
require_once __DIR__ . '/../../includes/init.php';
$user = require_role('entreprise');

$pageTitle = 'Ajouter un contact';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="card form-card">
    <div class="card-header">
        <h2>Ajouter un contact</h2>
        <a class="btn btn-sm btn-secondary" href="<?= url('pages/entreprise/contacts.php') ?>">Retour à la liste</a>
    </div>
    <form method="post" action="<?= url('actions/contact_save.php') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="0">
        <div class="form-group">
            <label for="type">Type de contact</label>
            <select name="type" id="type" required>
                <option value="client">Client</option>
                <option value="partenaire">Partenaire</option>
            </select>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="prenom">Prénom</label>
                <input type="text" name="prenom" id="prenom" required>
            </div>
            <div class="form-group">
                <label for="nom">Nom</label>
                <input type="text" name="nom" id="nom" required>
            </div>
        </div>
        <div class="form-group">
            <label for="email">Email (login)</label>
            <input type="email" name="email" id="email" required>
        </div>
        <div class="form-group">
            <label for="telephone">Téléphone</label>
            <input type="tel" name="telephone" id="telephone">
        </div>
        <div class="form-group">
            <label for="extra1">Organisation (optionnel)</label>
            <input type="text" name="extra1" id="extra1" placeholder="Nom d’entreprise / organisation">
        </div>
        <div class="form-group">
            <label for="extra2">Précision (optionnel)</label>
            <input type="text" name="extra2" id="extra2" placeholder="Notes ou type de partenariat">
        </div>
        <div class="form-group">
            <label for="adresse">Adresse</label>
            <textarea name="adresse" id="adresse" rows="2"></textarea>
        </div>
        <div class="form-group">
            <label for="password">Mot de passe</label>
            <input type="password" name="password" id="password" required minlength="6">
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Créer le contact</button>
            <a class="btn btn-secondary" href="<?= url('pages/entreprise/contacts.php') ?>">Annuler</a>
        </div>
    </form>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
