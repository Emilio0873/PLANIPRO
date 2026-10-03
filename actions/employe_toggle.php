<?php
require_once __DIR__ . '/../includes/init.php';
verify_csrf();
$user = require_role('entreprise');
$entrepriseId = get_entreprise_id_for_user($user);
$id = (int) post('id', 0);

$stmt = db()->prepare("SELECT * FROM users WHERE id = ? AND entreprise_id = ? AND role = 'employe'");
$stmt->execute([$id, $entrepriseId]);
$emp = $stmt->fetch();
if (!$emp) {
    flash('error', 'Visiteur introuvable.');
    redirect('pages/entreprise/employes.php');
}

$new = $emp['actif'] ? 0 : 1;
db()->prepare('UPDATE users SET actif = ? WHERE id = ?')->execute([$new, $id]);
flash('success', $new ? 'Visiteur activé.' : 'Visiteur désactivé.');
redirect('pages/entreprise/employes.php');
