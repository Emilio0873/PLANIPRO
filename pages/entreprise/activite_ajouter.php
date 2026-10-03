<?php
require_once __DIR__ . '/../../includes/init.php';
$user = require_role('entreprise');
$entrepriseId = get_entreprise_id_for_user($user);

$emps = db()->prepare("SELECT id, prenom, nom FROM users WHERE entreprise_id = ? AND role = 'employe' AND actif = 1 ORDER BY nom");
$emps->execute([$entrepriseId]);
$employes = $emps->fetchAll();

$editId = (int) get('edit', 0);
$edit = null;
if ($editId) {
    $stmt = db()->prepare('SELECT * FROM activites WHERE id = ? AND entreprise_id = ?');
    $stmt->execute([$editId, $entrepriseId]);
    $edit = $stmt->fetch() ?: null;
    if (!$edit) {
        flash('error', 'Activité introuvable.');
        redirect('pages/entreprise/activites.php');
    }
}

$pageTitle = $edit ? 'Modifier l’activité' : 'Nouvelle activité';
$prog = (int) ($edit['progression'] ?? 0);
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="card form-card form-card--activity">
    <div class="card-header">
        <div>
            <h2><?= $edit ? 'Modifier l’activité' : 'Nouvelle activité' ?></h2>
            <p class="help-text help-text--flush">Décrivez la tâche, fixez la priorité et assignez un responsable.</p>
        </div>
        <a class="btn btn-sm btn-secondary" href="<?= url('pages/entreprise/activites.php') ?>">Retour</a>
    </div>

    <form method="post" action="<?= url('actions/activite_save.php') ?>" class="activity-form">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">

        <fieldset class="form-section">
            <legend>Informations</legend>
            <div class="form-group">
                <label for="titre">Titre <span class="req">*</span></label>
                <input type="text" id="titre" name="titre" value="<?= e($edit['titre'] ?? '') ?>"
                       required maxlength="200" placeholder="Ex. Préparer le dossier client">
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="5"
                          placeholder="Détails, objectifs, consignes…"><?= e($edit['description'] ?? '') ?></textarea>
            </div>
        </fieldset>

        <fieldset class="form-section">
            <legend>Suivi</legend>
            <div class="form-row form-row--3">
                <div class="form-group">
                    <label for="priorite">Priorité</label>
                    <select id="priorite" name="priorite">
                        <?php foreach (['basse', 'moyenne', 'haute', 'urgente'] as $p): ?>
                            <option value="<?= $p ?>" <?= ($edit['priorite'] ?? 'moyenne') === $p ? 'selected' : '' ?>>
                                <?= e(statut_label($p)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="actStatut">Statut</label>
                    <select name="statut" id="actStatut">
                        <?php foreach (['a_faire', 'en_cours', 'terminee', 'annulee'] as $s): ?>
                            <option value="<?= $s ?>" <?= ($edit['statut'] ?? 'a_faire') === $s ? 'selected' : '' ?>>
                                <?= e(statut_label($s)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="echeance">Échéance</label>
                    <input type="date" id="echeance" name="echeance" value="<?= e($edit['echeance'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label for="actProgression">Progression — <span id="actProgressionLabel"><?= $prog ?></span> %</label>
                <div class="progression-field">
                    <input type="range" id="actProgression" name="progression" min="0" max="100" step="5" value="<?= $prog ?>">
                    <output id="actProgressionOut" for="actProgression"><?= $prog ?> %</output>
                </div>
                <p class="help-text">À 100 %, le statut passe à « Terminé ». Au-delà de 0 %, le statut passe à « En cours ».</p>
            </div>
        </fieldset>

        <fieldset class="form-section">
            <legend>Affectation</legend>
            <div class="form-group">
                <label for="assigne_a">Responsable (visiteur)</label>
                <select id="assigne_a" name="assigne_a">
                    <option value="">Non assigné</option>
                    <?php foreach ($employes as $emp): ?>
                        <option value="<?= (int) $emp['id'] ?>" <?= ((int) ($edit['assigne_a'] ?? 0) === (int) $emp['id']) ? 'selected' : '' ?>>
                            <?= e($emp['prenom'] . ' ' . $emp['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!$employes): ?>
                    <p class="help-text">Aucun visiteur actif. Créez-en un depuis Visiteurs pour pouvoir assigner.</p>
                <?php endif; ?>
            </div>
        </fieldset>

        <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?= $edit ? 'Enregistrer les modifications' : 'Créer l’activité' ?></button>
            <a class="btn btn-secondary" href="<?= url('pages/entreprise/activites.php') ?>">Annuler</a>
        </div>
    </form>
</div>
<script>
(function () {
  var range = document.getElementById('actProgression');
  var out = document.getElementById('actProgressionOut');
  var pct = document.getElementById('actProgressionLabel');
  var statut = document.getElementById('actStatut');
  if (!range || !out) return;
  function sync() {
    var v = parseInt(range.value, 10) || 0;
    out.textContent = v + ' %';
    if (pct) pct.textContent = String(v);
    if (statut && v >= 100) statut.value = 'terminee';
    else if (statut && v > 0 && statut.value === 'a_faire') statut.value = 'en_cours';
  }
  range.addEventListener('input', sync);
  if (statut) {
    statut.addEventListener('change', function () {
      if (statut.value === 'terminee') { range.value = 100; sync(); }
      if (statut.value === 'a_faire') { range.value = 0; sync(); }
    });
  }
})();
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
