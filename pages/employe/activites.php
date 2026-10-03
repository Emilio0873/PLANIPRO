<?php
require_once __DIR__ . '/../../includes/init.php';
$user = require_role('employe');
$stmt = db()->prepare("SELECT * FROM activites WHERE assigne_a = ? ORDER BY FIELD(priorite,'urgente','haute','moyenne','basse'), echeance ASC");
$stmt->execute([$user['id']]);
$activites = $stmt->fetchAll();

$open = array_filter($activites, static fn($a) => in_array($a['statut'], ['a_faire', 'en_cours'], true));
$avgOpen = 0;
if ($open) {
    $sum = 0;
    foreach ($open as $a) {
        $sum += (int) ($a['progression'] ?? 0);
    }
    $avgOpen = (int) round($sum / count($open));
}

$pageTitle = 'Mes activités';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="card">
    <div class="card-header">
        <div>
            <h2>Mes activités</h2>
            <p class="help-text help-text--flush">Mettez à jour le statut et la progression de vos tâches.</p>
        </div>
        <?php if ($open): ?>
            <div class="act-avg">
                <span>Progression moyenne</span>
                <?= progression_bar_html($avgOpen, 'sm') ?>
            </div>
        <?php else: ?>
            <span class="count-pill"><?= count($activites) ?></span>
        <?php endif; ?>
    </div>
    <?php if (!$activites): ?>
        <div class="empty">Aucune activité ne vous est assignée pour le moment.</div>
    <?php else: ?>
        <div class="people-list">
            <?php foreach ($activites as $a): ?>
                <?php $prog = (int) ($a['progression'] ?? 0); ?>
                <article class="people-card people-card--stack">
                    <div class="people-card__main">
                        <div class="person-avatar" aria-hidden="true"><?= e(initials_from_text($a['titre'])) ?></div>
                        <div class="people-card__info">
                            <div class="people-card__title">
                                <strong><?= e($a['titre']) ?></strong>
                                <?= statut_badge($a['statut']) ?>
                                <?= statut_badge($a['priorite']) ?>
                            </div>
                            <div class="people-card__meta">
                                <span>Échéance : <?= e(format_date($a['echeance'])) ?></span>
                            </div>
                            <?php if ($a['description']): ?>
                                <p class="help-text mt-xs"><?= e($a['description']) ?></p>
                            <?php endif; ?>
                            <div class="mt-xs" style="max-width:220px"><?= progression_bar_html($prog, 'sm') ?></div>
                        </div>
                    </div>
                    <form method="post" action="<?= url('actions/activite_save.php') ?>" class="act-update-form people-card__actions">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                        <input type="hidden" name="titre" value="<?= e($a['titre']) ?>">
                        <input type="hidden" name="priorite" value="<?= e($a['priorite']) ?>">
                        <label class="sr-only" for="statut-<?= (int) $a['id'] ?>">Statut</label>
                        <select id="statut-<?= (int) $a['id'] ?>" name="statut" class="act-statut" aria-label="Statut">
                            <?php foreach (['a_faire', 'en_cours', 'terminee', 'annulee'] as $s): ?>
                                <option value="<?= $s ?>" <?= $a['statut'] === $s ? 'selected' : '' ?>><?= e(statut_label($s)) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="progression-field progression-field--inline">
                            <label class="sr-only" for="prog-<?= (int) $a['id'] ?>">Progression</label>
                            <input type="range" id="prog-<?= (int) $a['id'] ?>" name="progression" class="act-range"
                                   min="0" max="100" step="5" value="<?= $prog ?>" aria-label="Progression">
                            <output class="act-out"><?= $prog ?> %</output>
                        </div>
                        <button class="btn btn-sm btn-primary" type="submit">Enregistrer</button>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<script>
document.querySelectorAll('.act-update-form').forEach(function (form) {
  var range = form.querySelector('.act-range');
  var out = form.querySelector('.act-out');
  var statut = form.querySelector('.act-statut');
  if (!range || !out) return;
  function sync() {
    var v = parseInt(range.value, 10) || 0;
    out.textContent = v + ' %';
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
});
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
