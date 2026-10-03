<?php
require_once __DIR__ . '/../includes/init.php';
verify_csrf();
$user = require_role('entreprise');
$entrepriseId = get_entreprise_id_for_user($user);
$id = (int) post('id', 0);
$type = post('type', '');

$redirectTo = 'pages/entreprise/contacts.php';

if (!in_array($type, ['client', 'partenaire'], true)) {
    flash('error', 'Type invalide.');
    redirect($redirectTo);
}

$stmt = db()->prepare("SELECT * FROM users WHERE id = ? AND role = ? AND entreprise_id = ?");
$stmt->execute([$id, $type, $entrepriseId]);
$row = $stmt->fetch();
if (!$row) {
    flash('error', 'Compte introuvable.');
    redirect($redirectTo);
}

$new = $row['actif'] ? 0 : 1;
db()->prepare('UPDATE users SET actif = ? WHERE id = ?')->execute([$new, $id]);
flash('success', $new ? 'Compte activé.' : 'Compte désactivé.');
redirect($redirectTo);
