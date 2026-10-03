<?php
require_once __DIR__ . '/../../includes/init.php';
$user = require_role('entreprise');

$pageTitle = 'Ajouter un visiteur';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="card form-card">
    <div class="card-header">
        <h2>Ajouter un visiteur</h2>
        <a class="btn btn-sm btn-secondary" href="<?= url('pages/entreprise/employes.php') ?>">Retour à la liste</a>
    </div>
    <form method="post" action="<?= url('actions/employe_save.php') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="0">
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
            <label for="password">Mot de passe</label>
            <input type="password" name="password" id="password" required minlength="6">
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Créer le compte</button>
            <a class="btn btn-secondary" href="<?= url('pages/entreprise/employes.php') ?>">Annuler</a>
        </div>
    </form>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
