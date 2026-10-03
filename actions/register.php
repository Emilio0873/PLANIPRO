<?php
require_once __DIR__ . '/../includes/init.php';
verify_csrf();

$prenom = post('prenom', '');
$nom = post('nom', '');
$email = strtolower(post('email', ''));
$telephone = post('telephone', '');
$password = post('password', '');
$passwordConfirm = post('password_confirm', '');
$raison = post('raison_sociale', '');
$secteur = post('secteur', '');
$adresse = post('adresse', '');

if ($prenom === '' || $nom === '' || $email === '' || $password === '' || $raison === '') {
    flash('error', 'Veuillez remplir tous les champs obligatoires.');
    redirect('pages/auth/register.php');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash('error', 'Email invalide.');
    redirect('pages/auth/register.php');
}

if (strlen($password) < 6 || $password !== $passwordConfirm) {
    flash('error', 'Les mots de passe doivent correspondre (min. 6 caractères).');
    redirect('pages/auth/register.php');
}

$check = db()->prepare('SELECT id FROM users WHERE email = ?');
$check->execute([$email]);
if ($check->fetch()) {
    flash('error', 'Cet email est déjà utilisé.');
    redirect('pages/auth/register.php');
}

$logoUpload = handle_entreprise_logo_upload('logo');
if (!$logoUpload['ok'] || !$logoUpload['uploaded']) {
    flash('error', $logoUpload['error'] ?: 'La photo / logo de l’entreprise est obligatoire.');
    redirect('pages/auth/register.php');
}
$logoPath = $logoUpload['path'];

$hash = password_hash($password, PASSWORD_DEFAULT);
$pdo = db();

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, role, nom, prenom, telephone) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$email, $hash, 'entreprise', $nom, $prenom, $telephone ?: null]);
    $userId = (int) $pdo->lastInsertId();

    $stmt = $pdo->prepare('INSERT INTO entreprises (user_id, raison_sociale, adresse, secteur, logo) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$userId, $raison, $adresse ?: null, $secteur ?: null, $logoPath]);

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    delete_entreprise_logo_file($logoPath);
    flash('error', 'Erreur lors de l\'inscription. Réessayez.');
    redirect('pages/auth/register.php');
}

attempt_login($email, $password);
flash('success', 'Compte entreprise créé. Vous pouvez maintenant ajouter clients et partenaires.');
redirect('pages/entreprise/dashboard.php');
