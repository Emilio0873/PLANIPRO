<?php
require_once __DIR__ . '/../../includes/init.php';
$user = require_role('entreprise');
$entrepriseId = get_entreprise_id_for_user($user);

$contacts = [];
if ($entrepriseId) {
    $c = db()->prepare("SELECT id, prenom, nom, role, email FROM users WHERE entreprise_id = ? AND role IN ('client','partenaire','employe') AND actif = 1 ORDER BY FIELD(role,'employe','client','partenaire'), nom, prenom");
    $c->execute([$entrepriseId]);
    $contacts = $c->fetchAll();
}

$roleLabels = [
    'employe' => 'Visiteur',
    'client' => 'Client',
    'partenaire' => 'Partenaire',
];

// Valeurs préremplies après une erreur (session flash old input)
$old = $_SESSION['rdv_form_old'] ?? [];
unset($_SESSION['rdv_form_old']);

$oldDemandeur = isset($old['demandeur_id']) ? (int) $old['demandeur_id'] : null;
$wantNew = $oldDemandeur === 0 || ($oldDemandeur === null && !$contacts);

$pageTitle = 'Ajouter un rendez-vous';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="card form-card">
    <div class="card-header">
        <h2>Ajouter un rendez-vous</h2>
        <a class="btn btn-sm btn-secondary" href="<?= url('pages/entreprise/rendez_vous.php') ?>">Retour à la liste</a>
    </div>
    <p class="help-text help-text--flush">La <strong>date/heure de fin</strong> doit être <strong>après</strong> la date/heure de début. Exemple : début 29/09/2026 09:00 → fin 29/09/2026 10:00.</p>
    <form method="post" action="<?= url('actions/rdv_entreprise_save.php') ?>" id="rdvCreateForm" novalidate>
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="demandeur_id">Visiteur / Client / Partenaire</label>
            <select name="demandeur_id" id="demandeur_id" data-contact-switch required>
                <option value="0"<?= $wantNew ? ' selected' : '' ?>>+ Nouveau contact</option>
                <?php foreach ($contacts as $c): ?>
                    <?php
                    $sel = (!$wantNew && $oldDemandeur !== null)
                        ? ((int) $c['id'] === $oldDemandeur)
                        : (!$wantNew && $oldDemandeur === null && (int) $c['id'] === (int) ($contacts[0]['id'] ?? 0));
                    ?>
                    <option value="<?= (int) $c['id'] ?>"<?= $sel ? ' selected' : '' ?>><?= e(($roleLabels[$c['role']] ?? ucfirst($c['role'])) . ' — ' . $c['prenom'] . ' ' . $c['nom'] . ' (' . $c['email'] . ')') ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="nouveauContactFields" class="contact-inline"<?= $wantNew ? '' : ' hidden' ?>>
            <div class="form-row form-row--3">
                <div class="form-group">
                    <label for="contact_type">Type</label>
                    <select name="contact_type" id="contact_type">
                        <?php
                        $ctype = $old['contact_type'] ?? 'client';
                        foreach (['employe' => 'Visiteur', 'client' => 'Client', 'partenaire' => 'Partenaire'] as $val => $lab):
                        ?>
                            <option value="<?= e($val) ?>"<?= $ctype === $val ? ' selected' : '' ?>><?= e($lab) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="contact_prenom">Prénom</label>
                    <input type="text" name="contact_prenom" id="contact_prenom" value="<?= e($old['contact_prenom'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="contact_nom">Nom</label>
                    <input type="text" name="contact_nom" id="contact_nom" value="<?= e($old['contact_nom'] ?? '') ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="contact_email">Email (login)</label>
                    <input type="email" name="contact_email" id="contact_email" value="<?= e($old['contact_email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="contact_telephone">Téléphone</label>
                    <input type="tel" name="contact_telephone" id="contact_telephone" value="<?= e($old['contact_telephone'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label for="contact_password">Mot de passe</label>
                <input type="password" name="contact_password" id="contact_password" minlength="6" autocomplete="new-password">
                <span class="help-text">Minimum 6 caractères (pour que le contact puisse se connecter).</span>
            </div>
        </div>

        <div class="form-group">
            <label for="titre">Titre</label>
            <input type="text" name="titre" id="titre" required value="<?= e($old['titre'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="motif">Motif</label>
            <textarea name="motif" id="motif" rows="2"><?= e($old['motif'] ?? '') ?></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="date_debut">Début</label>
                <input type="datetime-local" name="date_debut" id="date_debut" required
                       value="<?= e($old['date_debut'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="date_fin">Fin</label>
                <input type="datetime-local" name="date_fin" id="date_fin" required
                       value="<?= e($old['date_fin'] ?? '') ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="lieu">Lieu</label>
                <input type="text" name="lieu" id="lieu" value="<?= e($old['lieu'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="statut">Statut</label>
                <select name="statut" id="statut">
                    <?php $st = $old['statut'] ?? 'accepte'; ?>
                    <option value="accepte"<?= $st === 'accepte' ? ' selected' : '' ?>>Confirmé</option>
                    <option value="en_attente"<?= $st === 'en_attente' ? ' selected' : '' ?>>En attente</option>
                </select>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer le rendez-vous</button>
            <a class="btn btn-secondary" href="<?= url('pages/entreprise/rendez_vous.php') ?>">Annuler</a>
        </div>
    </form>
</div>
<script>
(function () {
  const debut = document.getElementById('date_debut');
  const fin = document.getElementById('date_fin');
  const form = document.getElementById('rdvCreateForm');
  if (!debut || !fin || !form) return;

  function pad(n) { return String(n).padStart(2, '0'); }
  function toLocalValue(d) {
    return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate())
      + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
  }

  // Valeurs par défaut : maintenant → +1 h
  if (!debut.value) {
    const now = new Date();
    now.setMinutes(0, 0, 0);
    now.setHours(now.getHours() + 1);
    debut.value = toLocalValue(now);
    const end = new Date(now.getTime() + 60 * 60 * 1000);
    fin.value = toLocalValue(end);
  }

  debut.addEventListener('change', function () {
    if (!debut.value) return;
    const start = new Date(debut.value);
    if (Number.isNaN(start.getTime())) return;
    const end = new Date(start.getTime() + 60 * 60 * 1000);
    if (!fin.value || new Date(fin.value) <= start) {
      fin.value = toLocalValue(end);
    }
    fin.min = debut.value;
  });

  form.addEventListener('submit', function (e) {
    if (!debut.value || !fin.value) {
      e.preventDefault();
      alert('Veuillez indiquer la date et l\'heure de début et de fin.');
      return;
    }
    const start = new Date(debut.value);
    const end = new Date(fin.value);
    if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) {
      e.preventDefault();
      alert('Format de date/heure incorrect. Choisissez une date et une heure valides.');
      return;
    }
    if (end <= start) {
      e.preventDefault();
      alert('La date/heure de FIN doit être APRÈS la date/heure de DÉBUT.\n\nExemple : début 29/09/2026 09:00 et fin 29/09/2026 10:00.');
      fin.focus();
    }
  });
})();
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
