<?php
require_once __DIR__ . '/../includes/init.php';
verify_csrf();
$user = require_role(['client', 'partenaire']);

$entrepriseId = (int) post('entreprise_id', 0);
$titre = post('titre', '');
$motif = post('motif', '');
$dateDebut = post('date_debut', '');
$dateFin = post('date_fin', '');
$lieu = post('lieu', '');
$id = (int) post('id', 0);

$back = 'pages/' . $user['role'] . '/rdv_demande.php';
if ($id > 0) {
    $back .= '?id=' . $id;
} elseif ($entrepriseId) {
    $back .= '?entreprise_id=' . $entrepriseId;
}

// Client / partenaire : uniquement leur entreprise liée
if (!empty($user['entreprise_id'])) {
    $entrepriseId = (int) $user['entreprise_id'];
}

if ($titre === '' || $dateDebut === '' || $dateFin === '' || !$entrepriseId) {
    flash('error', 'Veuillez remplir tous les champs obligatoires (titre, début, fin).');
    redirect($back);
}

$debut = parse_datetime_local($dateDebut);
$fin = parse_datetime_local($dateFin);
if (!$debut || !$fin) {
    flash('error', 'Format de date invalide. Réessayez avec le sélecteur de date.');
    redirect($back);
}
if ($fin <= $debut) {
    flash('error', 'La date/heure de fin doit être après le début.');
    redirect($back);
}

$ent = db()->prepare('SELECT e.*, u.id AS owner_id FROM entreprises e JOIN users u ON u.id = e.user_id WHERE e.id = ?');
$ent->execute([$entrepriseId]);
$entreprise = $ent->fetch();
if (!$entreprise) {
    flash('error', 'Entreprise introuvable.');
    redirect(role_home($user['role']));
}

$type = $user['role'] === 'client' ? 'client' : 'partenaire';
$pdo = db();

try {
    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM rendez_vous WHERE id = ? AND demandeur_id = ? AND statut = 'en_attente'");
        $stmt->execute([$id, $user['id']]);
        $rdv = $stmt->fetch();
        if (!$rdv) {
            flash('error', 'Demande non modifiable.');
            redirect('pages/' . $user['role'] . '/rendez_vous.php');
        }
        $stmt = $pdo->prepare('UPDATE rendez_vous SET titre = ?, motif = ?, date_debut = ?, date_fin = ?, lieu = ?, entreprise_id = ? WHERE id = ?');
        $stmt->execute([$titre, $motif ?: null, $debut->format('Y-m-d H:i:s'), $fin->format('Y-m-d H:i:s'), $lieu ?: null, $entrepriseId, $id]);
        log_historique((int) $user['id'], 'rendez_vous', $id, 'modification', $titre);
        notify((int) $entreprise['owner_id'], 'Demande RDV modifiée', $user['prenom'] . ' a modifié la demande : ' . $titre, 'info', 'pages/entreprise/rendez_vous.php');
        flash('success', 'Demande mise à jour.');
    } else {
        $stmt = $pdo->prepare('INSERT INTO rendez_vous (entreprise_id, demandeur_id, type_demandeur, titre, motif, date_debut, date_fin, lieu, statut) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $entrepriseId,
            $user['id'],
            $type,
            $titre,
            $motif ?: null,
            $debut->format('Y-m-d H:i:s'),
            $fin->format('Y-m-d H:i:s'),
            $lieu ?: null,
            'en_attente',
        ]);
        $newId = (int) $pdo->lastInsertId();
        log_historique((int) $user['id'], 'rendez_vous', $newId, 'creation', $titre);
        notify((int) $entreprise['owner_id'], 'Nouvelle demande de rendez-vous', $user['prenom'] . ' ' . $user['nom'] . ' : ' . $titre, 'info', 'pages/entreprise/rendez_vous.php');
        flash('success', 'Demande de rendez-vous envoyée.');
    }
} catch (Throwable $e) {
    flash('error', 'Impossible d\'enregistrer le rendez-vous. Vérifiez les informations saisies.');
    redirect($back);
}

redirect('pages/' . $user['role'] . '/rendez_vous.php');
