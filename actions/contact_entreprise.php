<?php
require_once __DIR__ . '/../includes/init.php';
verify_csrf();

$entrepriseId = (int) post('entreprise_id', 0);
$entreprise = get_entreprise_publique($entrepriseId);
if (!$entreprise) {
    flash('error', 'Entreprise introuvable.');
    redirect('index.php#entreprises');
}

$nom = post('nom', '');
$email = post('email', '');
$telephone = post('telephone', '');
$sujet = post('sujet', 'Message');
$message = post('message', '');
$user = current_user();

if ($nom === '' || $email === '' || $message === '') {
    flash('error', 'Nom, email et message sont obligatoires.');
    redirect('pages/public/contacter.php?entreprise_id=' . $entrepriseId);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash('error', 'Email invalide.');
    redirect('pages/public/contacter.php?entreprise_id=' . $entrepriseId);
}

if ($user && (int) $user['id'] === (int) $entreprise['owner_user_id']) {
    flash('error', 'Action non autorisée.');
    redirect('index.php#entreprises');
}

$token = bin2hex(random_bytes(16));
$pdo = db();
$pdo->prepare("
    INSERT INTO contact_public (entreprise_id, expediteur_user_id, nom, email, telephone, sujet, message, public_token)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
")->execute([
    $entrepriseId,
    $user ? (int) $user['id'] : null,
    $nom,
    $email,
    $telephone !== '' ? $telephone : null,
    $sujet !== '' ? $sujet : 'Message',
    $message,
    $token,
]);
$contactId = (int) $pdo->lastInsertId();

// Notification initiale pour le visiteur public
$pdo->prepare('INSERT INTO notifications_publiques (public_token, contact_public_id, entreprise_id, titre, message, type) VALUES (?, ?, ?, ?, ?, ?)')
    ->execute([
        $token,
        $contactId,
        $entrepriseId,
        'Message envoyé',
        'Votre message a bien été transmis à ' . $entreprise['raison_sociale'] . '. Vous serez notifié ici lorsque l’entreprise répondra (acceptation / refus du rendez-vous et identifiants de connexion).',
        'info',
    ]);

public_guest_remember_token($token);

$ownerId = (int) $entreprise['owner_user_id'];

// Si l'expéditeur est connecté, ouvrir aussi une conversation messagerie
if ($user && (int) $user['id'] !== $ownerId && can_message_user($user, $ownerId)) {
    $body = trim(($sujet !== '' ? $sujet : 'Message') . "\n\n" . $message);
    $convId = find_or_create_conversation((int) $user['id'], $ownerId);
    send_message($convId, (int) $user['id'], $body);
}

notify(
    $ownerId,
    'Nouveau message public',
    $nom . ' vous a écrit : ' . truncate_text($sujet . ' — ' . $message, 100),
    'info',
    'pages/entreprise/messages_recus.php'
);

flash('success', $user
    ? 'Message envoyé. Une conversation a aussi été ouverte dans la messagerie.'
    : 'Message envoyé. Suivez les réponses via l’icône de notification.');
redirect($user ? 'pages/messagerie/index.php' : 'pages/public/notifications.php');
