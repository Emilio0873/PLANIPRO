<?php
require_once __DIR__ . '/../includes/init.php';
verify_csrf();
$user = require_login();

$convId = (int) post('conversation_id', 0);
$body = post('body', '');

$stmt = db()->prepare('SELECT * FROM conversations WHERE id = ?');
$stmt->execute([$convId]);
$conv = $stmt->fetch();

if (!$conv || !user_in_conversation($conv, (int) $user['id'])) {
    flash('error', 'Conversation introuvable.');
    redirect('pages/messagerie/index.php');
}

$other = conversation_other_user($conv, (int) $user['id']);
if (!$other || !can_message_user($user, (int) $other['id'])) {
    flash('error', 'Envoi non autorisé.');
    redirect('pages/messagerie/index.php');
}

if (!send_message($convId, (int) $user['id'], $body)) {
    flash('error', 'Message vide.');
    redirect('pages/messagerie/conversation.php?id=' . $convId);
}

notify(
    (int) $other['id'],
    'Nouveau message',
    $user['prenom'] . ' ' . $user['nom'] . ' : ' . truncate_text(trim($body), 80),
    'info',
    'pages/messagerie/conversation.php?id=' . $convId
);

redirect('pages/messagerie/conversation.php?id=' . $convId);
