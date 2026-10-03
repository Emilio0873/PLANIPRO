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
$stmt = db()->prepare('SELECT id FROM contact_public WHERE id = ? AND entreprise_id = ?');
$stmt->execute([$contactId, $entrepriseId]);
if (!$stmt->fetch()) {
    flash('error', 'Message introuvable.');
    redirect('pages/entreprise/messages_recus.php');
}

db()->prepare('DELETE FROM contact_public WHERE id = ? AND entreprise_id = ?')
    ->execute([$contactId, $entrepriseId]);

flash('success', 'Message supprimé.');
redirect('pages/entreprise/messages_recus.php');
