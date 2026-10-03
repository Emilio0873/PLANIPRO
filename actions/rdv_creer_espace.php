<?php
require_once __DIR__ . '/../includes/init.php';
verify_csrf();
$user = require_role('entreprise');
$entrepriseId = get_entreprise_id_for_user($user);
if (!$entrepriseId) {
    flash('error', 'Entreprise introuvable.');
    redirect(role_home('entreprise'));
}

$rdvId = (int) post('rdv_id', 0);
$stmt = db()->prepare('SELECT r.*, u.email, u.prenom, u.nom, u.telephone FROM rendez_vous r JOIN users u ON u.id = r.demandeur_id WHERE r.id = ? AND r.entreprise_id = ?');
$stmt->execute([$rdvId, $entrepriseId]);
$rdv = $stmt->fetch();

if (!$rdv) {
    flash('error', 'Rendez-vous introuvable.');
    redirect('pages/entreprise/rendez_vous.php');
}

if ($rdv['statut'] !== 'accepte') {
    flash('error', 'Le rendez-vous doit d’abord être confirmé.');
    redirect('pages/entreprise/rendez_vous.php');
}

// Cherche un message public lié à cet email pour ouvrir le formulaire
$c = db()->prepare('SELECT id FROM contact_public WHERE entreprise_id = ? AND email = ? ORDER BY created_at DESC LIMIT 1');
$c->execute([$entrepriseId, strtolower((string) $rdv['email'])]);
$contactId = (int) $c->fetchColumn();

if ($contactId) {
    flash('info', 'Complétez le compte visiteur (mot de passe), puis envoyez la notification.');
    redirect('pages/entreprise/visiteur_creer.php?contact_id=' . $contactId);
}

flash('info', 'Aucun message public lié : créez le visiteur depuis Visiteurs, ou depuis Messages reçus.');
redirect('pages/entreprise/employes.php');
