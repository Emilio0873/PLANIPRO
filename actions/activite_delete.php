<?php
require_once __DIR__ . '/../includes/init.php';
verify_csrf();
$user = require_role('entreprise');
$entrepriseId = get_entreprise_id_for_user($user);
$id = (int) post('id', 0);

$stmt = db()->prepare('SELECT * FROM activites WHERE id = ? AND entreprise_id = ?');
$stmt->execute([$id, $entrepriseId]);
$act = $stmt->fetch();
if (!$act) {
    flash('error', 'Activité introuvable.');
    redirect('pages/entreprise/activites.php');
}
db()->prepare('DELETE FROM activites WHERE id = ?')->execute([$id]);
log_historique((int) $user['id'], 'activite', $id, 'suppression', $act['titre']);
flash('success', 'Activité supprimée.');
redirect('pages/entreprise/activites.php');
