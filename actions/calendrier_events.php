<?php
require_once __DIR__ . '/../includes/init.php';
$user = require_role(['entreprise', 'employe']);
$entrepriseId = get_entreprise_id_for_user($user);
header('Content-Type: application/json; charset=utf-8');

$events = [];

$rdvSql = "
    SELECT r.id, r.titre, r.motif, r.date_debut, r.date_fin, r.lieu, r.statut, r.type_demandeur,
           u.prenom, u.nom, u.email
    FROM rendez_vous r
    JOIN users u ON u.id = r.demandeur_id
    WHERE r.entreprise_id = ? AND r.statut IN ('en_attente','accepte','reporte')
";
$rdvParams = [$entrepriseId];
if ($user['role'] === 'employe') {
    $rdvSql .= ' AND r.demandeur_id = ?';
    $rdvParams[] = (int) $user['id'];
}
$rdv = db()->prepare($rdvSql);
$rdv->execute($rdvParams);
foreach ($rdv->fetchAll() as $r) {
    $color = match ($r['statut']) {
        'accepte' => '#0f1f38',
        'reporte' => '#1a3358',
        default => '#334155',
    };
    $events[] = [
        'id' => 'rdv-' . $r['id'],
        'title' => '[RDV] ' . $r['titre'],
        'start' => str_replace(' ', 'T', $r['date_debut']),
        'end' => str_replace(' ', 'T', $r['date_fin']),
        'backgroundColor' => $color,
        'borderColor' => $color,
        'extendedProps' => [
            'kind' => 'rdv',
            'entityId' => (int) $r['id'],
            'titre' => $r['titre'],
            'statut' => $r['statut'],
            'statutLabel' => statut_label($r['statut']),
            'debut' => format_datetime($r['date_debut']),
            'fin' => format_datetime($r['date_fin']),
            'lieu' => $r['lieu'] ?: '—',
            'motif' => $r['motif'] ?: '',
            'personne' => trim($r['prenom'] . ' ' . $r['nom']),
            'email' => $r['email'],
            'type' => $r['type_demandeur'] === 'employe' ? 'Visiteur' : ucfirst($r['type_demandeur']),
            'urlListe' => url('pages/' . ($user['role'] === 'entreprise' ? 'entreprise' : 'employe') . '/rendez_vous.php'),
        ],
    ];
}

$acts = db()->prepare("
    SELECT a.id, a.titre, a.description, a.echeance, a.priorite, a.statut, a.progression, a.assigne_a,
           u.prenom, u.nom
    FROM activites a
    LEFT JOIN users u ON u.id = a.assigne_a
    WHERE a.entreprise_id = ? AND a.statut IN ('a_faire','en_cours') AND a.echeance IS NOT NULL
");
$acts->execute([$entrepriseId]);
foreach ($acts->fetchAll() as $a) {
    if ($user['role'] === 'employe' && (int) $a['assigne_a'] !== (int) $user['id']) {
        continue;
    }
    $color = match ($a['priorite']) {
        'urgente', 'haute' => '#1e293b',
        'moyenne' => '#12253f',
        default => '#0a1628',
    };
    $assigne = $a['prenom'] ? trim($a['prenom'] . ' ' . $a['nom']) : 'Non assigné';
    $events[] = [
        'id' => 'act-' . $a['id'],
        'title' => '[Act.] ' . $a['titre'],
        'start' => $a['echeance'],
        'allDay' => true,
        'backgroundColor' => $color,
        'borderColor' => $color,
        'extendedProps' => [
            'kind' => 'activite',
            'entityId' => (int) $a['id'],
            'titre' => $a['titre'],
            'statut' => $a['statut'],
            'statutLabel' => statut_label($a['statut']),
            'priorite' => statut_label($a['priorite']),
            'progression' => (int) ($a['progression'] ?? 0),
            'echeance' => format_date($a['echeance']),
            'assigne' => $assigne,
            'description' => $a['description'] ?: '',
            'urlListe' => url('pages/' . ($user['role'] === 'entreprise' ? 'entreprise' : 'employe') . '/activites.php'),
        ],
    ];
}

echo json_encode($events);
