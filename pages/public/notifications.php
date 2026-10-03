<?php
require_once __DIR__ . '/../../includes/init.php';

$tokenGet = get('token', '');
if ($tokenGet !== '' && preg_match('/^[a-f0-9]{32,64}$/i', $tokenGet)) {
    public_guest_remember_token($tokenGet);
}

$tokens = public_guest_tokens();
$lire = (int) get('lire', 0);
if ($lire && $tokens) {
    $in = implode(',', array_fill(0, count($tokens), '?'));
    $stmt = db()->prepare("SELECT id, message FROM notifications_publiques WHERE id = ? AND public_token IN ($in)");
    $stmt->execute(array_merge([$lire], $tokens));
    $row = $stmt->fetch();
    if ($row) {
        $scrubbed = preg_replace(
            '/Mot de passe \(affichage unique\)\s*:\s*.+/u',
            'Mot de passe : [masqué après lecture]',
            (string) $row['message']
        );
        $scrubbed = preg_replace(
            '/Mot de passe\s*:\s*(?!\[masqué).+/u',
            'Mot de passe : [masqué après lecture]',
            $scrubbed
        );
        db()->prepare("UPDATE notifications_publiques SET lu = 1, message = ? WHERE id = ? AND public_token IN ($in)")
            ->execute(array_merge([$scrubbed, $lire], $tokens));
    }
}

if (get('lire_tout') === '1' && $tokens) {
    $in = implode(',', array_fill(0, count($tokens), '?'));
    $rows = db()->prepare("SELECT id, message FROM notifications_publiques WHERE public_token IN ($in) AND lu = 0");
    $rows->execute($tokens);
    $upd = db()->prepare('UPDATE notifications_publiques SET lu = 1, message = ? WHERE id = ?');
    foreach ($rows->fetchAll() as $row) {
        $scrubbed = preg_replace(
            '/Mot de passe \(affichage unique\)\s*:\s*.+/u',
            'Mot de passe : [masqué après lecture]',
            (string) $row['message']
        );
        $scrubbed = preg_replace(
            '/Mot de passe\s*:\s*(?!\[masqué).+/u',
            'Mot de passe : [masqué après lecture]',
            $scrubbed
        );
        $upd->execute([$scrubbed, $row['id']]);
    }
}

$notifs = list_public_notifications($tokens);
$unread = unread_public_notifications_count($tokens);
$login = url('pages/auth/login.php');
$pageTitle = 'Mes notifications';
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
      <div class="card-header" style="padding:0;margin-bottom:1rem;border:0">
        <h1 style="margin:0;font-size:1.4rem">Mes notifications</h1>
        <?php if ($unread > 0): ?>
          <a class="btn btn-sm btn-secondary" href="?lire_tout=1">Tout marquer lu</a>
        <?php endif; ?>
      </div>
      <p class="sub" style="margin-top:0">Espace réservé aux personnes qui ont écrit à une entreprise sur PLANIPRO.</p>

      <?php foreach (get_flashes() as $f): ?>
        <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
      <?php endforeach; ?>

      <?php if (!$tokens): ?>
        <div class="empty">Aucune demande suivie. Écrivez d’abord à une entreprise depuis l’accueil.</div>
        <p class="auth-foot"><a href="<?= e(url('index.php#entreprises')) ?>">← Voir les entreprises</a></p>
      <?php elseif (!$notifs): ?>
        <div class="empty">Pas encore de notification.</div>
      <?php else: ?>
        <?php foreach ($notifs as $n): ?>
          <div class="notif-item <?= $n['lu'] ? '' : 'unread' ?>">
            <div class="notif-item__body">
              <strong><?= e($n['titre']) ?></strong>
              <div class="help-text"><?= e($n['raison_sociale']) ?> · <?= e(format_datetime($n['created_at'])) ?></div>
              <div style="margin-top:.5rem;white-space:pre-wrap"><?= e($n['message']) ?></div>
            </div>
            <?php if (!$n['lu']): ?>
              <a class="btn btn-sm btn-secondary" href="?lire=<?= (int) $n['id'] ?>">Lu</a>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
        <div class="form-actions" style="margin-top:1.25rem">
          <a class="btn btn-primary" href="<?= e($login) ?>">Se connecter à mon espace</a>
          <a class="btn btn-secondary" href="<?= e(url('index.php#entreprises')) ?>">Retour accueil</a>
        </div>
      <?php endif; ?>
    </div>
  </main>
  <script src="<?= e(asset('js/app.js')) ?>?v=2"></script>
</body>
</html>
