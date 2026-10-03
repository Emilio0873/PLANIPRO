<?php
require_once __DIR__ . '/../includes/init.php';
verify_csrf();
$user = require_login();

$toId = (int) post('to_user_id', 0);
$body = post('body', '');

if (!$toId || !can_message_user($user, $toId)) {
    flash('error', 'Destinataire non autorisé.');
    redirect('pages/messagerie/index.php');
}

if (trim($body) === '') {
    flash('error', 'Écrivez un message.');
    redirect('pages/messagerie/index.php');
}

$convId = find_or_create_conversation((int) $user['id'], $toId);
send_message($convId, (int) $user['id'], $body);

$to = get_user_by_id($toId);
if ($to) {
    notify(
        $toId,
        'Nouveau message',
        $user['prenom'] . ' ' . $user['nom'] . ' vous a envoyé un message.',
        'info',
        'pages/messagerie/conversation.php?id=' . $convId
    );
}

flash('success', 'Message envoyé.');
redirect('pages/messagerie/conversation.php?id=' . $convId);
