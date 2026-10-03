<?php
require_once __DIR__ . '/../includes/init.php';
verify_csrf();
$user = require_role('entreprise');
$entrepriseId = get_entreprise_id_for_user($user);

$id = (int) post('id', 0);
$prenom = post('prenom', '');
$nom = post('nom', '');
$email = strtolower(post('email', ''));
$telephone = post('telephone', '');
$password = post('password', '');

$list = 'pages/entreprise/employes.php';
$form = $id > 0
    ? 'pages/entreprise/employe_edit.php?id=' . $id
    : 'pages/entreprise/visiteur_ajouter.php';

if ($prenom === '' || $nom === '' || $email === '') {
    flash('error', 'Champs obligatoires manquants.');
    redirect($form);
}

$pdo = db();

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND entreprise_id = ? AND role = 'employe'");
    $stmt->execute([$id, $entrepriseId]);
    $emp = $stmt->fetch();
    if (!$emp) {
        flash('error', 'Visiteur introuvable.');
        redirect('pages/entreprise/employes.php');
    }
    $check = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
    $check->execute([$email, $id]);
    if ($check->fetch()) {
        flash('error', 'Cet email est déjà utilisé.');
        redirect('pages/entreprise/employe_edit.php?id=' . $id);
    }
    $stmt = $pdo->prepare('UPDATE users SET prenom = ?, nom = ?, email = ?, telephone = ? WHERE id = ?');
    $stmt->execute([$prenom, $nom, $email, $telephone ?: null, $id]);
    if ($password !== '') {
        if (strlen($password) < 6) {
            flash('error', 'Mot de passe trop court.');
            redirect('pages/entreprise/employe_edit.php?id=' . $id);
        }
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
    }
    flash('success', 'Visiteur mis à jour.');
} else {
    if (strlen($password) < 6) {
        flash('error', 'Mot de passe requis (min. 6 caractères).');
        redirect($form);
    }
    $check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $check->execute([$email]);
    if ($check->fetch()) {
        flash('error', 'Cet email est déjà utilisé.');
        redirect($form);
    }
    $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, role, nom, prenom, telephone, entreprise_id) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $email,
        password_hash($password, PASSWORD_DEFAULT),
        'employe',
        $nom,
        $prenom,
        $telephone ?: null,
        $entrepriseId,
    ]);
    $newId = (int) $pdo->lastInsertId();
    notify($newId, 'Compte créé', 'Votre compte visiteur PLANIPRO a été créé. Vous pouvez vous connecter.', 'info', 'pages/employe/dashboard.php');
    flash('success', 'Visiteur créé. Il peut se connecter avec son email.');
}

redirect('pages/entreprise/employes.php');
