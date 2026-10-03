<?php
require_once __DIR__ . '/../includes/init.php';
verify_csrf();
$user = require_role('entreprise');
$entrepriseId = get_entreprise_id_for_user($user);

$type = post('type', ''); // client | partenaire
$id = (int) post('id', 0);
$redirectTo = 'pages/entreprise/contacts.php';
$form = 'pages/entreprise/contact_ajouter.php';

if (!in_array($type, ['client', 'partenaire'], true)) {
    flash('error', 'Choisissez Client ou Partenaire.');
    redirect($form);
}

$prenom = post('prenom', '');
$nom = post('nom', '');
$email = strtolower(post('email', ''));
$telephone = post('telephone', '');
$password = post('password', '');
$extra1 = post('extra1', '');
$extra2 = post('extra2', '');
$adresse = post('adresse', '');

if ($prenom === '' || $nom === '' || $email === '') {
    flash('error', 'Nom, prénom et email sont obligatoires.');
    redirect($id > 0 ? $redirectTo : $form);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash('error', 'Email invalide.');
    redirect($id > 0 ? $redirectTo : $form);
}

$pdo = db();

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = ? AND entreprise_id = ?");
    $stmt->execute([$id, $type, $entrepriseId]);
    $row = $stmt->fetch();
    if (!$row) {
        flash('error', 'Compte introuvable.');
        redirect($redirectTo);
    }

    $check = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
    $check->execute([$email, $id]);
    if ($check->fetch()) {
        flash('error', 'Cet email est déjà utilisé.');
        redirect($redirectTo);
    }

    $pdo->prepare('UPDATE users SET prenom = ?, nom = ?, email = ?, telephone = ? WHERE id = ?')
        ->execute([$prenom, $nom, $email, $telephone ?: null, $id]);

    if ($password !== '') {
        if (strlen($password) < 6) {
            flash('error', 'Mot de passe trop court (min. 6).');
            redirect($redirectTo);
        }
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
    }

    if ($type === 'client') {
        $pdo->prepare('UPDATE clients SET adresse = ?, entreprise_nom = ?, notes = ? WHERE user_id = ?')
            ->execute([$adresse ?: null, $extra1 ?: null, $extra2 ?: null, $id]);
    } else {
        $pdo->prepare('UPDATE partenaires SET organisation = ?, type_partenariat = ?, adresse = ?, notes = ? WHERE user_id = ?')
            ->execute([$extra1 ?: null, $extra2 ?: null, $adresse ?: null, null, $id]);
    }

    flash('success', ucfirst($type) . ' mis à jour.');
    redirect($redirectTo);
}

if (strlen($password) < 6) {
    flash('error', 'Mot de passe requis (min. 6 caractères) pour le nouveau compte.');
    redirect($form);
}

$check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$check->execute([$email]);
if ($check->fetch()) {
    flash('error', 'Cet email est déjà utilisé.');
    redirect($form);
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, role, nom, prenom, telephone, entreprise_id) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $email,
        password_hash($password, PASSWORD_DEFAULT),
        $type,
        $nom,
        $prenom,
        $telephone ?: null,
        $entrepriseId,
    ]);
    $newId = (int) $pdo->lastInsertId();

    if ($type === 'client') {
        $pdo->prepare('INSERT INTO clients (user_id, adresse, entreprise_nom, notes) VALUES (?, ?, ?, ?)')
            ->execute([$newId, $adresse ?: null, $extra1 ?: null, $extra2 ?: null]);
    } else {
        $pdo->prepare('INSERT INTO partenaires (user_id, organisation, type_partenariat, adresse) VALUES (?, ?, ?, ?)')
            ->execute([$newId, $extra1 ?: null, $extra2 ?: null, $adresse ?: null]);
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', 'Impossible de créer le compte.');
    redirect($form);
}

notify(
    $newId,
    'Compte créé',
    'Votre compte ' . $type . ' PLANIPRO a été créé par l\'entreprise. Connectez-vous avec votre email.',
    'info',
    'pages/' . $type . '/dashboard.php'
);

flash('success', 'Compte ' . $type . ' créé. Il peut se connecter avec cet email.');
redirect($redirectTo);
