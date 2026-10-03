<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/SimplePdf.php';
$user = require_role('entreprise');
$entrepriseId = get_entreprise_id_for_user($user);
$type = get('type', 'rdv');
$pdo = db();

$ent = $pdo->prepare('SELECT raison_sociale FROM entreprises WHERE id = ?');
$ent->execute([$entrepriseId]);
$nomEnt = $ent->fetchColumn() ?: 'Entreprise';

$pdf = new SimplePdf('Rapport PLANIPRO');
$pdf->addTitle($nomEnt);

if ($type === 'rdv') {
    $pdf->addTitle('Rapport des rendez-vous');
    $rows = $pdo->prepare("SELECT r.titre, CONCAT(u.prenom,' ',u.nom) demandeur, r.type_demandeur, r.date_debut, r.statut FROM rendez_vous r JOIN users u ON u.id = r.demandeur_id WHERE r.entreprise_id = ? ORDER BY r.date_debut DESC");
    $rows->execute([$entrepriseId]);
    $data = [];
    foreach ($rows->fetchAll() as $r) {
        $data[] = [$r['titre'], $r['demandeur'], $r['type_demandeur'], format_datetime($r['date_debut']), statut_label($r['statut'])];
    }
    $pdf->addTable(['Titre', 'Demandeur', 'Type', 'Début', 'Statut'], $data);
} elseif ($type === 'activites') {
    $pdf->addTitle('Rapport des activités');
    $rows = $pdo->prepare("SELECT a.titre, a.priorite, CONCAT(IFNULL(u.prenom,''),' ',IFNULL(u.nom,'')) assigne, a.echeance, a.progression, a.statut FROM activites a LEFT JOIN users u ON u.id = a.assigne_a WHERE a.entreprise_id = ? ORDER BY a.created_at DESC");
    $rows->execute([$entrepriseId]);
    $data = [];
    foreach ($rows->fetchAll() as $a) {
        $data[] = [$a['titre'], statut_label($a['priorite']), trim($a['assigne']) ?: '—', format_date($a['echeance']), ((int) ($a['progression'] ?? 0)) . ' %', statut_label($a['statut'])];
    }
    $pdf->addTable(['Titre', 'Priorité', 'Assigné', 'Échéance', 'Progression', 'Statut'], $data);
} elseif ($type === 'clients') {
    $pdf->addTitle('Rapport des clients');
    $rows = $pdo->prepare("SELECT CONCAT(u.prenom,' ',u.nom) nom, u.email, u.telephone, COUNT(r.id) nb FROM rendez_vous r JOIN users u ON u.id = r.demandeur_id WHERE r.entreprise_id = ? AND r.type_demandeur = 'client' GROUP BY u.id");
    $rows->execute([$entrepriseId]);
    $data = [];
    foreach ($rows->fetchAll() as $c) {
        $data[] = [$c['nom'], $c['email'], $c['telephone'] ?: '—', $c['nb']];
    }
    $pdf->addTable(['Nom', 'Email', 'Téléphone', 'Nb RDV'], $data);
} else {
    $pdf->addTitle('Rapport des partenaires');
    $rows = $pdo->prepare("SELECT CONCAT(u.prenom,' ',u.nom) nom, IFNULL(p.organisation,'—') org, u.email, COUNT(r.id) nb FROM rendez_vous r JOIN users u ON u.id = r.demandeur_id LEFT JOIN partenaires p ON p.user_id = u.id WHERE r.entreprise_id = ? AND r.type_demandeur = 'partenaire' GROUP BY u.id");
    $rows->execute([$entrepriseId]);
    $data = [];
    foreach ($rows->fetchAll() as $p) {
        $data[] = [$p['nom'], $p['org'], $p['email'], $p['nb']];
    }
    $pdf->addTable(['Nom', 'Organisation', 'Email', 'Nb RDV'], $data);
}

$pdf->output('rapport-' . $type . '.pdf');
