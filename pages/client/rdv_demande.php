<?php
require_once __DIR__ . '/../../includes/init.php';
$user = require_role('client');
$entrepriseId = (int) get('entreprise_id', 0);
$editId = (int) get('id', 0);
$rdv = null;

if (empty($user['entreprise_id'])) {
    flash('error', 'Compte non lié à une entreprise.');
    redirect('pages/client/dashboard.php');
}

// Le client ne peut demander qu'à son entreprise
if ($entrepriseId && $entrepriseId !== (int) $user['entreprise_id']) {
    flash('error', 'Vous ne pouvez prendre rendez-vous qu\'avec votre entreprise.');
    redirect('pages/client/rendez_vous.php');
}
$entrepriseId = (int) $user['entreprise_id'];

if ($editId) {
    $stmt = db()->prepare("SELECT * FROM rendez_vous WHERE id = ? AND demandeur_id = ? AND statut = 'en_attente'");
    $stmt->execute([$editId, $user['id']]);
    $rdv = $stmt->fetch();
    if (!$rdv) {
        flash('error', 'Demande non modifiable.');
        redirect('pages/client/rendez_vous.php');
    }
    $entrepriseId = (int) $rdv['entreprise_id'];
}

$ent = db()->prepare('SELECT * FROM entreprises WHERE id = ?');
$ent->execute([$entrepriseId]);
$entreprise = $ent->fetch();
if (!$entreprise) {
    flash('error', 'Entreprise introuvable.');
    redirect('pages/client/entreprises.php');
}

$pageTitle = $rdv ? 'Modifier la demande' : 'Demander un rendez-vous';
require_once __DIR__ . '/../../includes/header.php';

$debutVal = $rdv ? (new DateTime($rdv['date_debut']))->format('Y-m-d\TH:i') : '';
$finVal = $rdv ? (new DateTime($rdv['date_fin']))->format('Y-m-d\TH:i') : '';
?>
<div class="card form-card">
    <p>Entreprise : <strong><?= e($entreprise['raison_sociale']) ?></strong></p>
    <form method="post" action="<?= url('actions/rdv_demande.php') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="entreprise_id" value="<?= (int) $entrepriseId ?>">
        <input type="hidden" name="id" value="<?= (int) $editId ?>">
        <div class="form-group">
            <label for="titre">Titre</label>
            <input type="text" name="titre" id="titre" value="<?= e($rdv['titre'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label for="motif">Motif</label>
            <textarea name="motif" id="motif"><?= e($rdv['motif'] ?? '') ?></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="date_debut">Début</label>
                <input type="datetime-local" name="date_debut" id="date_debut" value="<?= e($debutVal) ?>" required>
            </div>
            <div class="form-group">
                <label for="date_fin">Fin</label>
                <input type="datetime-local" name="date_fin" id="date_fin" value="<?= e($finVal) ?>" required>
            </div>
        </div>
        <div class="form-group">
            <label for="lieu">Lieu</label>
            <input type="text" name="lieu" id="lieu" value="<?= e($rdv['lieu'] ?? '') ?>">
        </div>
        <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?= $rdv ? 'Mettre à jour' : 'Envoyer la demande' ?></button>
            <a class="btn btn-secondary" href="<?= url('pages/client/rendez_vous.php') ?>">Annuler</a>
        </div>
    </form>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
