<?php
require_once __DIR__ . '/../../includes/init.php';
if (is_logged_in()) {
    redirect(role_home(current_user()['role']));
}
$flashes = get_flashes();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connexion — PLANIPRO</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
</head>
<body class="auth-body">
  <div class="auth-bg" aria-hidden="true">
    <span class="auth-orb auth-orb--1"></span>
    <span class="auth-orb auth-orb--2"></span>
    <span class="auth-orb auth-orb--3"></span>
    <span class="auth-grid"></span>
  </div>

  <main class="auth-wrap">
    <div class="auth-panel auth-panel--login">
      <aside class="auth-side">
        <a class="auth-side__logo" href="<?= e(url('index.php')) ?>">
          <img src="<?= e(logo_url()) ?>" alt="PlaniPro" class="brand-logo brand-logo--auth">
        </a>
        <h2 class="auth-side__title">Bienvenue sur PlaniPro</h2>
        <p class="auth-side__text">Organisez vos rendez-vous, activités et équipes dans un espace clair et sécurisé.</p>
        <ul class="auth-side__points">
          <li>Planning centralisé</li>
          <li>Messagerie intégrée</li>
          <li>Suivi en temps réel</li>
        </ul>
      </aside>

      <div class="auth-box">
        <div class="auth-box__intro">
          <h1>Connexion</h1>
          <p class="sub">Entreprises, visiteurs, clients et partenaires — un seul espace.</p>
        </div>
        <?php foreach ($flashes as $f): ?>
          <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
        <?php endforeach; ?>
        <form method="post" action="<?= e(url('actions/login.php')) ?>" class="auth-form" autocomplete="off">
          <?= csrf_field() ?>
          <div class="form-group auth-field" style="--step:0">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required autocomplete="off" value="" placeholder="vous@exemple.com">
          </div>
          <div class="form-group auth-field" style="--step:1">
            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" required autocomplete="new-password" value="" placeholder="••••••••">
          </div>
          <div class="form-actions auth-field" style="--step:2">
            <button type="submit" class="btn btn-primary btn-block auth-submit">Se connecter</button>
          </div>
        </form>
        <p class="auth-foot">
          Entreprise ?
          <a href="<?= e(url('pages/auth/register.php')) ?>">Créer un compte</a>
          ·
          <a href="<?= e(url('index.php')) ?>">Accueil</a>
        </p>
      </div>
    </div>
  </main>
  <script src="<?= e(asset('js/app.js')) ?>?v=2"></script>
</body>
</html>
