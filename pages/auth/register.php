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
  <title>Inscription — PLANIPRO</title>
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
    <div class="auth-panel auth-panel--register">
      <aside class="auth-side">
        <a class="auth-side__logo" href="<?= e(url('index.php')) ?>">
          <img src="<?= e(logo_url()) ?>" alt="PlaniPro" class="brand-logo brand-logo--auth">
        </a>
        <h2 class="auth-side__title">Lancez votre entreprise</h2>
        <p class="auth-side__text">Créez votre compte en quelques minutes, puis invitez visiteurs, clients et partenaires.</p>
        <ul class="auth-side__points">
          <li>Espace dédié à votre société</li>
          <li>Gestion des contacts et RDV</li>
          <li>Tableaux de bord clairs</li>
        </ul>
      </aside>

      <div class="auth-box auth-box--wide">
        <div class="auth-box__intro">
          <h1>Inscription entreprise</h1>
          <p class="sub">Renseignez les informations de votre société pour démarrer.</p>
        </div>
        <?php foreach ($flashes as $f): ?>
          <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
        <?php endforeach; ?>
        <form method="post" action="<?= e(url('actions/register.php')) ?>" class="auth-form" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <input type="hidden" name="role" value="entreprise">
          <div class="form-row auth-field" style="--step:0">
            <div class="form-group">
              <label for="prenom">Prénom</label>
              <input type="text" id="prenom" name="prenom" required>
            </div>
            <div class="form-group">
              <label for="nom">Nom</label>
              <input type="text" id="nom" name="nom" required>
            </div>
          </div>
          <div class="form-group auth-field" style="--step:1">
            <label for="raison_sociale">Raison sociale</label>
            <input type="text" id="raison_sociale" name="raison_sociale" required>
          </div>
          <div class="form-group auth-field" style="--step:2">
            <label for="logo">Photo / logo de l’entreprise</label>
            <input type="file" id="logo" name="logo" accept="image/jpeg,image/png,image/webp,image/gif" required>
            <p class="help-text">Obligatoire — JPG, PNG, WEBP ou GIF (max. 2 Mo). Visible sur l’accueil public.</p>
          </div>
          <div class="form-group auth-field" style="--step:3">
            <label for="secteur">Secteur</label>
            <input type="text" id="secteur" name="secteur">
          </div>
          <div class="form-group auth-field" style="--step:4">
            <label for="adresse_ent">Adresse</label>
            <textarea id="adresse_ent" name="adresse" rows="2"></textarea>
          </div>
          <div class="form-group auth-field" style="--step:5">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>
          </div>
          <div class="form-group auth-field" style="--step:6">
            <label for="telephone">Téléphone</label>
            <input type="tel" id="telephone" name="telephone">
          </div>
          <div class="form-row auth-field" style="--step:7">
            <div class="form-group">
              <label for="password">Mot de passe</label>
              <input type="password" id="password" name="password" required minlength="6">
            </div>
            <div class="form-group">
              <label for="password_confirm">Confirmation</label>
              <input type="password" id="password_confirm" name="password_confirm" required minlength="6">
            </div>
          </div>
          <div class="form-actions auth-field" style="--step:8">
            <button type="submit" class="btn btn-primary btn-block auth-submit">Commencer</button>
          </div>
        </form>
        <p class="auth-foot">Déjà inscrit ? <a href="<?= e(url('pages/auth/login.php')) ?>">Se connecter</a></p>
      </div>
    </div>
  </main>
  <script src="<?= e(asset('js/app.js')) ?>?v=2"></script>
</body>
</html>
