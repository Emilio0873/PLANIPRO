<?php
require_once __DIR__ . '/../includes/init.php';
verify_csrf();
$user = require_role(['entreprise', 'client', 'partenaire', 'employe']);

$id = (int) post('id', 0);
$action = post('action', '');
$commentaire = post('commentaire', '');
$nouvelleDateDebut = post('date_debut', '');
$nouvelleDateFin = post('date_fin', '');

$pdo = db();
$stmt = $pdo->prepare('SELECT r.*, e.user_id AS owner_id FROM rendez_vous r JOIN entreprises e ON e.id = r.entreprise_id WHERE r.id = ?');
$stmt->execute([$id]);
$rdv = $stmt->fetch();
if (!$rdv) {
    flash('error', 'Rendez-vous introuvable.');
    redirect(role_home($user['role']));
}

$isOwner = $user['role'] === 'entreprise' && (int) $rdv['owner_id'] === (int) $user['id'];
$isDemandeur = in_array($user['role'], ['client', 'partenaire', 'employe'], true)
    && (int) $rdv['demandeur_id'] === (int) $user['id'];

if (!$isOwner && !$isDemandeur) {
    flash('error', 'Action non autorisée.');
    redirect(role_home($user['role']));
}

$demandeurRedirect = match ($user['role']) {
    'employe' => 'pages/employe/rendez_vous.php',
    'entreprise' => 'pages/entreprise/rendez_vous.php',
    default => 'pages/' . $user['role'] . '/rendez_vous.php',
};

$newStatut = null;
$details = '';

if ($isDemandeur) {
    if ($action === 'annuler' && in_array($rdv['statut'], ['en_attente', 'accepte', 'reporte'], true)) {
        $newStatut = 'annule';
        $details = 'Annulé par le demandeur';
        notify((int) $rdv['owner_id'], 'Rendez-vous annulé', 'Le rendez-vous « ' . $rdv['titre'] . ' » a été annulé.', 'warning', 'pages/entreprise/rendez_vous.php');
    } else {
        flash('error', 'Action impossible.');
        redirect($demandeurRedirect);
    }
} else {
    switch ($action) {
        case 'accepter':
            if ($rdv['statut'] !== 'en_attente' && $rdv['statut'] !== 'reporte') {
                flash('error', 'Statut incompatible.');
                redirect('pages/entreprise/rendez_vous.php');
            }
            $newStatut = 'accepte';
            $details = 'Accepté';
            break;
        case 'refuser':
            if ($rdv['statut'] !== 'en_attente') {
                flash('error', 'Statut incompatible.');
                redirect('pages/entreprise/rendez_vous.php');
            }
            $newStatut = 'refuse';
            $details = 'Refusé';
            break;
        case 'annuler':
            $newStatut = 'annule';
            $details = 'Annulé par l\'entreprise';
            break;
        case 'terminer':
            $newStatut = 'termine';
            $details = 'Marqué terminé';
            break;
        case 'reporter':
            $debut = DateTime::createFromFormat('Y-m-d\TH:i', $nouvelleDateDebut) ?: DateTime::createFromFormat('Y-m-d H:i', $nouvelleDateDebut);
            $fin = DateTime::createFromFormat('Y-m-d\TH:i', $nouvelleDateFin) ?: DateTime::createFromFormat('Y-m-d H:i', $nouvelleDateFin);
            if (!$debut || !$fin || $fin <= $debut) {
                flash('error', 'Nouvelles dates invalides.');
                redirect('pages/entreprise/rendez_vous.php');
            }
            $pdo->prepare('UPDATE rendez_vous SET date_debut = ?, date_fin = ?, statut = ?, commentaire_entreprise = ? WHERE id = ?')
                ->execute([$debut->format('Y-m-d H:i:s'), $fin->format('Y-m-d H:i:s'), 'reporte', $commentaire ?: null, $id]);
            log_historique((int) $user['id'], 'rendez_vous', $id, 'report', $commentaire);
            $notifyPath = $rdv['type_demandeur'] === 'employe'
                ? 'pages/employe/rendez_vous.php'
                : 'pages/' . $rdv['type_demandeur'] . '/rendez_vous.php';
            notify((int) $rdv['demandeur_id'], 'Rendez-vous reporté', '« ' . $rdv['titre'] . ' » a été reporté au ' . $debut->format('d/m/Y H:i'), 'warning', $notifyPath);
            flash('success', 'Rendez-vous reporté.');
            redirect('pages/entreprise/rendez_vous.php');
            break;
        default:
            flash('error', 'Action inconnue.');
            redirect('pages/entreprise/rendez_vous.php');
    }
}

if ($newStatut) {
    $pdo->prepare('UPDATE rendez_vous SET statut = ?, commentaire_entreprise = COALESCE(?, commentaire_entreprise) WHERE id = ?')
        ->execute([$newStatut, $commentaire ?: null, $id]);
    log_historique((int) $user['id'], 'rendez_vous', $id, $action, $details);
    if ($isOwner) {
        $notifyPath = $rdv['type_demandeur'] === 'employe'
            ? 'pages/employe/rendez_vous.php'
            : 'pages/' . $rdv['type_demandeur'] . '/rendez_vous.php';
        notify((int) $rdv['demandeur_id'], 'Mise à jour rendez-vous', '« ' . $rdv['titre'] . ' » : ' . statut_label($newStatut), 'info', $notifyPath);
    }
    flash('success', 'Rendez-vous mis à jour.');
}

redirect($demandeurRedirect);
