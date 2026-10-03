<?php
require_once __DIR__ . '/../includes/init.php';
verify_csrf();
$user = require_role(['entreprise', 'employe']);
$entrepriseId = get_entreprise_id_for_user($user);
if (!$entrepriseId) {
    flash('error', 'Entreprise introuvable.');
    redirect(role_home($user['role']));
}

$id = (int) post('id', 0);
$titre = post('titre', '');
$description = post('description', '');
$priorite = post('priorite', 'moyenne');
$statut = post('statut', 'a_faire');
$progression = (int) post('progression', 0);
$echeance = post('echeance', '') ?: null;
$assigne = (int) post('assigne_a', 0) ?: null;

$priorites = ['basse', 'moyenne', 'haute', 'urgente'];
$statuts = ['a_faire', 'en_cours', 'terminee', 'annulee'];
if ($titre === '' || !in_array($priorite, $priorites, true) || !in_array($statut, $statuts, true)) {
    flash('error', 'Données invalides.');
    if ($user['role'] === 'entreprise') {
        redirect($id > 0 ? 'pages/entreprise/activite_ajouter.php?edit=' . $id : 'pages/entreprise/activite_ajouter.php');
    }
    redirect('pages/employe/activites.php');
}

[$statut, $progression] = sync_activite_progression($statut, $progression);

if ($assigne) {
    $chk = db()->prepare("SELECT id FROM users WHERE id = ? AND entreprise_id = ? AND role = 'employe'");
    $chk->execute([$assigne, $entrepriseId]);
    if (!$chk->fetch() && $user['role'] === 'entreprise') {
        $assigne = null;
    }
}

$pdo = db();
if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM activites WHERE id = ? AND entreprise_id = ?');
    $stmt->execute([$id, $entrepriseId]);
    $act = $stmt->fetch();
    if (!$act) {
        flash('error', 'Activité introuvable.');
        redirect('pages/entreprise/activites.php');
    }
    if ($user['role'] === 'employe') {
        if ((int) $act['assigne_a'] !== (int) $user['id']) {
            flash('error', 'Non autorisé.');
            redirect('pages/employe/activites.php');
        }
        $pdo->prepare('UPDATE activites SET statut = ?, progression = ? WHERE id = ?')
            ->execute([$statut, $progression, $id]);
        log_historique((int) $user['id'], 'activite', $id, 'progression', $statut . ' / ' . $progression . '%');
        $owner = $pdo->prepare('SELECT user_id FROM entreprises WHERE id = ?');
        $owner->execute([$entrepriseId]);
        $ownerId = (int) $owner->fetchColumn();
        notify(
            $ownerId,
            'Progression mise à jour',
            $user['prenom'] . ' a mis « ' . $act['titre'] . ' » à ' . $progression . '% (' . statut_label($statut) . ')',
            'info',
            'pages/entreprise/activites.php'
        );
        flash('success', 'Progression enregistrée (' . $progression . ' %).');
        redirect('pages/employe/activites.php');
    }
    $pdo->prepare('UPDATE activites SET titre=?, description=?, priorite=?, statut=?, progression=?, echeance=?, assigne_a=? WHERE id=?')
        ->execute([$titre, $description ?: null, $priorite, $statut, $progression, $echeance, $assigne, $id]);
    log_historique((int) $user['id'], 'activite', $id, 'modification', $titre . ' (' . $progression . '%)');
    if ($assigne && $assigne !== (int) ($act['assigne_a'] ?? 0)) {
        notify($assigne, 'Nouvelle activité', 'Vous avez été assigné à : ' . $titre, 'info', 'pages/employe/activites.php');
    }
    flash('success', 'Activité mise à jour.');
} else {
    if ($user['role'] !== 'entreprise') {
        flash('error', 'Seule l\'entreprise peut créer une activité.');
        redirect('pages/employe/activites.php');
    }
    $pdo->prepare('INSERT INTO activites (entreprise_id, assigne_a, cree_par, titre, description, priorite, statut, progression, echeance) VALUES (?,?,?,?,?,?,?,?,?)')
        ->execute([$entrepriseId, $assigne, $user['id'], $titre, $description ?: null, $priorite, $statut, $progression, $echeance]);
    $newId = (int) $pdo->lastInsertId();
    log_historique((int) $user['id'], 'activite', $newId, 'creation', $titre);
    if ($assigne) {
        notify($assigne, 'Nouvelle activité', 'Vous avez été assigné à : ' . $titre, 'info', 'pages/employe/activites.php');
    }
    flash('success', 'Activité créée.');
}
redirect('pages/entreprise/activites.php');
