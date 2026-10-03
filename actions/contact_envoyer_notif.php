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
$userId = (int) post('user_id', 0);

$contact = null;
if ($contactId) {
    $stmt = db()->prepare('SELECT * FROM contact_public WHERE id = ? AND entreprise_id = ?');
    $stmt->execute([$contactId, $entrepriseId]);
    $contact = $stmt->fetch();
} elseif ($userId) {
    $contact = contact_public_by_visiteur($entrepriseId, $userId);
}

if (!$contact) {
    flash('error', 'Contact public introuvable. Cette action concerne les comptes issus d’un message public.');
    redirect($contactId ? 'pages/entreprise/messages_recus.php' : 'pages/entreprise/employes.php');
}

if (empty($contact['public_token'])) {
    $token = bin2hex(random_bytes(16));
    db()->prepare('UPDATE contact_public SET public_token = ? WHERE id = ?')->execute([$token, $contact['id']]);
    $contact['public_token'] = $token;
}

// Si le compte existe mais le mot de passe à envoyer a déjà été consommé, demander de le renseigner
$passwordOverride = post('password', '');
if ($passwordOverride !== '') {
    if (strlen($passwordOverride) < 6) {
        flash('error', 'Mot de passe trop court pour la notification.');
        redirect('pages/entreprise/messages_recus.php');
    }
    if (!empty($contact['expediteur_user_id'])) {
        db()->prepare('UPDATE users SET password_hash = ? WHERE id = ? AND entreprise_id = ? AND role = ?')
            ->execute([password_hash($passwordOverride, PASSWORD_DEFAULT), $contact['expediteur_user_id'], $entrepriseId, 'employe']);
    }
    db()->prepare('UPDATE contact_public SET mdp_a_envoyer = ? WHERE id = ?')->execute([$passwordOverride, $contact['id']]);
    $contact['mdp_a_envoyer'] = $passwordOverride;
}

if (empty($contact['expediteur_user_id'])) {
    flash('error', 'Créez d’abord le compte visiteur avant d’envoyer la notification.');
    redirect('pages/entreprise/visiteur_creer.php?contact_id=' . (int) $contact['id']);
}

// Recharger
$stmt = db()->prepare('SELECT * FROM contact_public WHERE id = ?');
$stmt->execute([$contact['id']]);
$contact = $stmt->fetch();

try {
    send_public_visitor_notification($contact, 'Réponse de l’entreprise — vos informations');
} catch (Throwable $e) {
    flash('error', $e->getMessage() ?: 'Envoi impossible.');
    redirect('pages/entreprise/messages_recus.php');
}

flash('success', 'Notification envoyée. Le visiteur la verra via l’icône de notification sur l’accueil public.');
$back = post('user_id') ? 'pages/entreprise/employes.php' : 'pages/entreprise/messages_recus.php';
redirect($back);
