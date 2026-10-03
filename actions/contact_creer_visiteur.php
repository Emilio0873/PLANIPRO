<?php
require_once __DIR__ . '/../includes/init.php';
verify_csrf();
$user = require_role('entreprise');
$entrepriseId = get_entreprise_id_for_user($user);
if (!$entrepriseId) {
    flash('error', 'Entreprise introuvable.');
    redirect(role_home('entreprise'));
}

$contactId = (int) post('contact_id', 0);
$stmt = db()->prepare('SELECT * FROM contact_public WHERE id = ? AND entreprise_id = ?');
$stmt->execute([$contactId, $entrepriseId]);
$contact = $stmt->fetch();
if (!$contact) {
    flash('error', 'Message introuvable.');
    redirect('pages/entreprise/messages_recus.php');
}

$prenom = post('prenom', '');
$nom = post('nom', '');
$email = strtolower(post('email', ''));
$telephone = post('telephone', '');
$password = post('password', '');
$passwordConfirm = post('password_confirm', '');

if ($prenom === '' || $nom === '' || $email === '') {
    flash('error', 'Prénom, nom et email sont obligatoires.');
    redirect('pages/entreprise/visiteur_creer.php?contact_id=' . $contactId);
}

if (strlen($password) < 6 || $password !== $passwordConfirm) {
    flash('error', 'Mot de passe invalide (min. 6 caractères, confirmation identique).');
    redirect('pages/entreprise/visiteur_creer.php?contact_id=' . $contactId);
}

try {
    $result = create_visiteur_with_password(
        $entrepriseId,
        $email,
        $prenom,
        $nom,
        $password,
        $telephone !== '' ? $telephone : null,
        $contactId
    );
} catch (Throwable $e) {
    flash('error', $e->getMessage() ?: 'Impossible de créer le compte.');
    redirect('pages/entreprise/visiteur_creer.php?contact_id=' . $contactId);
}

$visiteur = $result['user'];
notify(
    (int) $visiteur['id'],
    'Compte visiteur créé',
    'Votre compte visiteur PLANIPRO a été préparé. Attendez la notification publique avec vos identifiants, ou connectez-vous si vous les avez déjà.',
    'info',
    'pages/employe/dashboard.php'
);

flash('success', 'Compte visiteur créé. Cliquez maintenant sur « Envoyer la notification » pour transmettre les identifiants.');
redirect('pages/entreprise/messages_recus.php');
