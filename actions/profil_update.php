<?php
require_once __DIR__ . '/../includes/init.php';
verify_csrf();
$user = require_login();

$section = post('section', 'compte');
$base = match ($user['role']) {
    'entreprise' => 'pages/entreprise/profil.php',
    'employe' => 'pages/employe/profil.php',
    'client' => 'pages/client/profil.php',
    'partenaire' => 'pages/partenaire/profil.php',
    default => 'pages/entreprise/profil.php',
};
$back = $base . '?section=' . urlencode($section);
$pdo = db();

if ($section === 'compte') {
    $prenom = post('prenom', '');
    $nom = post('nom', '');
    $telephone = post('telephone', '');

    if ($prenom === '' || $nom === '') {
        flash('error', 'Nom et prénom requis.');
        redirect($back);
    }

    $pdo->prepare('UPDATE users SET prenom = ?, nom = ?, telephone = ? WHERE id = ?')
        ->execute([$prenom, $nom, $telephone !== '' ? $telephone : null, $user['id']]);

    flash('success', 'Compte mis à jour.');
    redirect($back);
}

if ($section === 'infos') {
    if ($user['role'] === 'entreprise') {
        $current = $pdo->prepare('SELECT logo FROM entreprises WHERE user_id = ?');
        $current->execute([$user['id']]);
        $oldLogo = $current->fetchColumn() ?: null;

        $logoUpload = handle_entreprise_logo_upload('logo', $oldLogo ?: null);
        if (!$logoUpload['ok']) {
            flash('error', $logoUpload['error'] ?: 'Impossible de mettre à jour la photo.');
            redirect($back);
        }

        $logoValue = $logoUpload['uploaded'] ? $logoUpload['path'] : $oldLogo;

        $siteWeb = sanitize_http_url(post('site_web'));
        $pdo->prepare('UPDATE entreprises SET raison_sociale = ?, adresse = ?, secteur = ?, description = ?, site_web = ?, logo = ? WHERE user_id = ?')
            ->execute([
                post('raison_sociale', '') ?: 'Entreprise',
                post('adresse') ?: null,
                post('secteur') ?: null,
                post('description') ?: null,
                $siteWeb,
                $logoValue,
                $user['id'],
            ]);
        flash('success', $logoUpload['uploaded']
            ? 'Informations et photo de profil enregistrées.'
            : 'Informations entreprise enregistrées.');
        redirect($back);
    }

    if ($user['role'] === 'client') {
        $pdo->prepare('UPDATE clients SET adresse = ?, entreprise_nom = ?, notes = ? WHERE user_id = ?')
            ->execute([
                post('adresse') ?: null,
                post('entreprise_nom') ?: null,
                post('notes') ?: null,
                $user['id'],
            ]);
        flash('success', 'Coordonnées enregistrées.');
        redirect($back);
    }

    if ($user['role'] === 'partenaire') {
        $pdo->prepare('UPDATE partenaires SET organisation = ?, type_partenariat = ?, adresse = ?, notes = ? WHERE user_id = ?')
            ->execute([
                post('organisation') ?: null,
                post('type_partenariat') ?: null,
                post('adresse') ?: null,
                post('notes') ?: null,
                $user['id'],
            ]);
        flash('success', 'Organisation enregistrée.');
        redirect($back);
    }

    flash('error', 'Section non modifiable.');
    redirect($back);
}

if ($section === 'securite') {
    $current = post('password_current', '');
    $password = post('password', '');
    $passwordConfirm = post('password_confirm', '');

    $fresh = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
    $fresh->execute([$user['id']]);
    $hash = (string) $fresh->fetchColumn();

    if ($current === '' || !password_verify($current, $hash)) {
        flash('error', 'Mot de passe actuel incorrect.');
        redirect($back);
    }

    if (strlen($password) < 6 || $password !== $passwordConfirm) {
        flash('error', 'Nouveau mot de passe invalide (min. 6 caractères, confirmation requise).');
        redirect($back);
    }

    $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
        ->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);

    flash('success', 'Mot de passe modifié.');
    redirect($back);
}

flash('error', 'Section inconnue.');
redirect($base);
