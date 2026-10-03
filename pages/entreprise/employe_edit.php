<?php
require_once __DIR__ . '/../../includes/init.php';
$user = require_role('entreprise');
$entrepriseId = get_entreprise_id_for_user($user);
$id = (int) get('id', 0);

$stmt = db()->prepare("SELECT * FROM users WHERE id = ? AND entreprise_id = ? AND role = 'employe'");
$stmt->execute([$id, $entrepriseId]);
$emp = $stmt->fetch();
if (!$emp) {
    flash('error', 'Visiteur introuvable.');
    redirect('pages/entreprise/employes.php');
}

$pageTitle = 'Modifier visiteur';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="card form-card">
    <form method="post" action="<?= url('actions/employe_save.php') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $emp['id'] ?>">
        <div class="form-row">
            <div class="form-group">
                <label for="prenom">Prénom</label>
                <input type="text" name="prenom" id="prenom" value="<?= e($emp['prenom']) ?>" required>
            </div>
            <div class="form-group">
                <label for="nom">Nom</label>
                <input type="text" name="nom" id="nom" value="<?= e($emp['nom']) ?>" required>
            </div>
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" value="<?= e($emp['email']) ?>" required>
        </div>
        <div class="form-group">
            <label for="telephone">Téléphone</label>
            <input type="tel" name="telephone" id="telephone" value="<?= e($emp['telephone'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="password">Nouveau mot de passe (optionnel)</label>
            <input type="password" name="password" id="password" minlength="6">
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a class="btn btn-secondary" href="<?= url('pages/entreprise/employes.php') ?>">Retour</a>
        </div>
    </form>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
