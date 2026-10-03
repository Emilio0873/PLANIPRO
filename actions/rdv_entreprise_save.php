<?php
require_once __DIR__ . '/../includes/init.php';
verify_csrf();
$user = require_role('entreprise');
$entrepriseId = get_entreprise_id_for_user($user);

$demandeurId = (int) post('demandeur_id', 0);
$titre = post('titre', '');
$motif = post('motif', '');
$dateDebut = post('date_debut', '');
$dateFin = post('date_fin', '');
$lieu = post('lieu', '');
$statut = post('statut', 'accepte');

if (!in_array($statut, ['en_attente', 'accepte'], true)) {
    $statut = 'accepte';
}

$back = 'pages/entreprise/rendez_vous.php';
$form = 'pages/entreprise/rendez_vous_ajouter.php';

$rememberForm = static function () use ($form): void {
    $_SESSION['rdv_form_old'] = [
        'demandeur_id' => post('demandeur_id', '0'),
        'contact_type' => post('contact_type', 'client'),
        'contact_prenom' => post('contact_prenom', ''),
        'contact_nom' => post('contact_nom', ''),
        'contact_email' => post('contact_email', ''),
        'contact_telephone' => post('contact_telephone', ''),
        'titre' => post('titre', ''),
        'motif' => post('motif', ''),
        'date_debut' => post('date_debut', ''),
        'date_fin' => post('date_fin', ''),
        'lieu' => post('lieu', ''),
        'statut' => post('statut', 'accepte'),
    ];
};

if ($titre === '' || $dateDebut === '' || $dateFin === '' || !$entrepriseId) {
    $rememberForm();
    flash('error', 'Veuillez remplir le titre, la date/heure de début et la date/heure de fin.');
    redirect($form);
}

$pdo = db();

$demandeur = null;
$allowedRoles = ['client', 'partenaire', 'employe'];

if ($demandeurId > 0) {
    $chk = $pdo->prepare("SELECT id, role, prenom, nom FROM users WHERE id = ? AND entreprise_id = ? AND role IN ('client','partenaire','employe') AND actif = 1");
    $chk->execute([$demandeurId, $entrepriseId]);
    $demandeur = $chk->fetch();
    if (!$demandeur) {
        $rememberForm();
        flash('error', 'Personne introuvable (visiteur, client ou partenaire).');
        redirect($form);
    }
} else {
    $type = post('contact_type', 'employe');
    if (!in_array($type, $allowedRoles, true)) {
        $type = 'employe';
    }
    $prenom = trim(post('contact_prenom', ''));
    $nom = trim(post('contact_nom', ''));
    $email = strtolower(trim(post('contact_email', '')));
    $telephone = trim(post('contact_telephone', ''));
    $password = post('contact_password', '');

    if ($prenom === '' || $nom === '' || $email === '') {
        $rememberForm();
        flash('error', 'Pour un nouveau contact : prénom, nom et email sont obligatoires. Choisissez bien « + Nouveau contact » dans la liste.');
        redirect($form);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $rememberForm();
        flash('error', 'Email du contact invalide.');
        redirect($form);
    }
    if (strlen($password) < 6) {
        $rememberForm();
        flash('error', 'Mot de passe du contact requis (min. 6 caractères).');
        redirect($form);
    }

    $check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $check->execute([$email]);
    if ($check->fetch()) {
        $rememberForm();
        flash('error', 'Cet email est déjà utilisé. Choisissez le contact dans la liste, ou utilisez un autre email.');
        redirect($form);
    }

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, role, nom, prenom, telephone, entreprise_id) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $email,
            password_hash($password, PASSWORD_DEFAULT),
            $type,
            $nom,
            $prenom,
            $telephone ?: null,
            $entrepriseId,
        ]);
        $demandeurId = (int) $pdo->lastInsertId();

        if ($type === 'client') {
            $pdo->prepare('INSERT INTO clients (user_id, adresse, entreprise_nom, notes) VALUES (?, NULL, NULL, NULL)')
                ->execute([$demandeurId]);
        } elseif ($type === 'partenaire') {
            $pdo->prepare('INSERT INTO partenaires (user_id, organisation, type_partenariat, adresse) VALUES (?, NULL, NULL, NULL)')
                ->execute([$demandeurId]);
        }
        $pdo->commit();

        $demandeur = ['id' => $demandeurId, 'role' => $type, 'prenom' => $prenom, 'nom' => $nom];
        $home = $type === 'employe' ? 'pages/employe/dashboard.php' : 'pages/' . $type . '/dashboard.php';
        $label = $type === 'employe' ? 'visiteur' : $type;
        notify(
            $demandeurId,
            'Compte créé',
            'Votre compte ' . $label . ' PLANIPRO a été créé. Connectez-vous avec votre email.',
            'info',
            $home
        );
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $rememberForm();
        flash('error', 'Impossible de créer le contact. Vérifiez les informations saisies.');
        redirect($form);
    }
}

$debut = parse_datetime_local($dateDebut);
$fin = parse_datetime_local($dateFin);
if (!$debut || !$fin) {
    $rememberForm();
    flash('error', 'Format de date/heure invalide. Indiquez bien une date ET une heure (ex. 29/09/2026 09:00).');
    redirect($form);
}
if ($fin <= $debut) {
    $rememberForm();
    flash('error', 'La date/heure de FIN doit être après la date/heure de DÉBUT. Exemple : début 29/09/2026 09:00 et fin 29/09/2026 10:00 (pas une date antérieure).');
    redirect($form);
}

$rdvPath = match ($demandeur['role']) {
    'employe' => 'pages/employe/rendez_vous.php',
    'client' => 'pages/client/rendez_vous.php',
    'partenaire' => 'pages/partenaire/rendez_vous.php',
    default => 'pages/entreprise/rendez_vous.php',
};

try {
    $stmt = $pdo->prepare('INSERT INTO rendez_vous (entreprise_id, demandeur_id, type_demandeur, titre, motif, date_debut, date_fin, lieu, statut) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $entrepriseId,
        $demandeurId,
        $demandeur['role'],
        $titre,
        $motif ?: null,
        $debut->format('Y-m-d H:i:s'),
        $fin->format('Y-m-d H:i:s'),
        $lieu ?: null,
        $statut,
    ]);
    $newId = (int) $pdo->lastInsertId();
    log_historique((int) $user['id'], 'rendez_vous', $newId, 'creation_entreprise', $titre);
    notify($demandeurId, 'Nouveau rendez-vous', 'Un rendez-vous a été planifié : ' . $titre, 'info', $rdvPath);
    flash('success', 'Rendez-vous enregistré.');
} catch (Throwable $e) {
    $rememberForm();
    flash('error', 'Erreur lors de l\'enregistrement du rendez-vous. Réessayez.');
    redirect($form);
}

unset($_SESSION['rdv_form_old']);
redirect($back);
