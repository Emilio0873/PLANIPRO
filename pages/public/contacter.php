<?php
require_once __DIR__ . '/../../includes/init.php';

$entrepriseId = (int) get('entreprise_id', 0);
$entreprise = $entrepriseId ? get_entreprise_publique($entrepriseId) : null;
if (!$entreprise) {
    flash('error', 'Entreprise introuvable.');
    redirect('index.php#entreprises');
}

$user = current_user();
$pageTitle = 'Contacter ' . $entreprise['raison_sociale'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?> — PLANIPRO</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
</head>
<body class="auth-body">
  <?php
  $publicTokens = public_guest_tokens();
  $publicNotifCount = unread_public_notifications_count($publicTokens);
  ?>
  <?php if ($publicTokens): ?>
    <a class="lp-public-bell lp-public-bell--fixed" href="<?= e(url('pages/public/notifications.php')) ?>" title="Mes notifications" aria-label="Mes notifications">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5"/><path d="M9.5 17a2.5 2.5 0 0 0 5 0"/></svg>
      <?php if ($publicNotifCount > 0): ?><span class="lp-public-bell__dot"><?= (int) $publicNotifCount ?></span><?php endif; ?>
    </a>
  <?php endif; ?>
  <div class="auth-bg" aria-hidden="true">
    <span class="auth-orb auth-orb--1"></span>
    <span class="auth-orb auth-orb--2"></span>
    <span class="auth-grid"></span>
  </div>
  <main class="auth-wrap">
    <div class="auth-box auth-box--wide contact-box">
      <a class="logo" href="<?= e(url('index.php')) ?>">
        <img src="<?= e(logo_url()) ?>" alt="PlaniPro" class="brand-logo brand-logo--auth">
      </a>
      <div class="contact-ent-head">
        <?= entreprise_avatar_html($entreprise, 'contact-ent-logo') ?>
        <div>
          <h1>Écrire à <?= e($entreprise['raison_sociale']) ?></h1>
          <p class="sub">
            <?= e($entreprise['secteur'] ?: 'Entreprise') ?>
            <?php if (!empty($entreprise['adresse'])): ?> · <?= e($entreprise['adresse']) ?><?php endif; ?>
          </p>
        </div>
      </div>

      <?php foreach (get_flashes() as $f): ?>
        <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
      <?php endforeach; ?>

      <?php if ($user && (int) $user['id'] === (int) $entreprise['owner_user_id']): ?>
        <div class="alert alert-info">Vous ne pouvez pas vous écrire à vous-même.</div>
        <p class="auth-foot"><a href="<?= e(url('index.php#entreprises')) ?>">Retour aux entreprises</a></p>
      <?php else: ?>
        <form method="post" action="<?= e(url('actions/contact_entreprise.php')) ?>" class="auth-form">
          <?= csrf_field() ?>
          <input type="hidden" name="entreprise_id" value="<?= (int) $entreprise['id'] ?>">

          <div class="form-row">
            <div class="form-group">
              <label for="nom">Votre nom</label>
              <input type="text" id="nom" name="nom" required
                     value="<?= e($user ? trim($user['prenom'] . ' ' . $user['nom']) : '') ?>">
            </div>
            <div class="form-group">
              <label for="email">Votre email</label>
              <input type="email" id="email" name="email" required
                     value="<?= e($user['email'] ?? '') ?>">
            </div>
          </div>
          <div class="form-group">
            <label for="telephone">Téléphone (optionnel)</label>
            <input type="tel" id="telephone" name="telephone"
                   value="<?= e($user['telephone'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label for="sujet">Sujet</label>
            <input type="text" id="sujet" name="sujet" required placeholder="Ex. Demande d’information">
          </div>
          <div class="form-group">
            <label for="message">Message</label>
            <textarea id="message" name="message" rows="5" required placeholder="Écrivez votre message…"></textarea>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn btn-primary btn-block">Envoyer le message</button>
          </div>
        </form>
        <?php if ($user): ?>
          <p class="auth-foot help-text" style="margin-top:.85rem">
            Connecté en tant que <?= e($user['prenom'] . ' ' . $user['nom']) ?>.
            Le message sera aussi ouvert dans la messagerie PLANIPRO.
          </p>
        <?php else: ?>
          <p class="auth-foot">Déjà un compte ? <a href="<?= e(url('pages/auth/login.php')) ?>">Se connecter</a></p>
        <?php endif; ?>
        <p class="auth-foot"><a href="<?= e(url('index.php#entreprises')) ?>">← Retour aux entreprises</a></p>
      <?php endif; ?>
    </div>
  </main>
</body>
</html>
