<?php
require_once __DIR__ . '/../includes/init.php';
verify_csrf();

$email = strtolower(post('email', ''));
$password = post('password', '');

if ($email === '' || $password === '') {
    flash('error', 'Email et mot de passe requis.');
    redirect('pages/auth/login.php');
}

if (!attempt_login($email, $password)) {
    flash('error', 'Identifiants incorrects.');
    redirect('pages/auth/login.php');
}

$user = current_user();
flash('success', 'Bienvenue, ' . $user['prenom'] . ' !');
redirect(role_home($user['role']));
