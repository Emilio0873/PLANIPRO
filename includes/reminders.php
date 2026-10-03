<?php
/**
 * Crée des rappels in-app pour les RDV des prochaines 24h (une fois par RDV).
 */
function process_rdv_reminders(?array $user): void
{
    if (!$user) {
        return;
    }
    $pdo = db();

    if (in_array($user['role'], ['entreprise', 'employe'], true)) {
        $entrepriseId = get_entreprise_id_for_user($user);
        if (!$entrepriseId) {
            return;
        }
        $stmt = $pdo->prepare("
            SELECT r.* FROM rendez_vous r
            WHERE r.entreprise_id = ? AND r.statut IN ('accepte','reporte')
              AND r.date_debut BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 1 DAY)
        ");
        $stmt->execute([$entrepriseId]);
    } else {
        $stmt = $pdo->prepare("
            SELECT r.* FROM rendez_vous r
            WHERE r.demandeur_id = ? AND r.statut IN ('accepte','reporte')
              AND r.date_debut BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 1 DAY)
        ");
        $stmt->execute([$user['id']]);
    }

    foreach ($stmt->fetchAll() as $rdv) {
        $exists = $pdo->prepare("SELECT id FROM notifications WHERE user_id = ? AND type = 'rappel' AND message LIKE ? LIMIT 1");
        $marker = '%#' . $rdv['id'] . '%';
        $exists->execute([$user['id'], $marker]);
        if ($exists->fetch()) {
            continue;
        }
        notify(
            (int) $user['id'],
            'Rappel de rendez-vous',
            'Rappel : « ' . $rdv['titre'] . ' » le ' . format_datetime($rdv['date_debut']) . ' (#' . $rdv['id'] . ')',
            'rappel',
            $user['role'] === 'entreprise' || $user['role'] === 'employe'
                ? 'pages/' . $user['role'] . '/calendrier.php'
                : 'pages/' . $user['role'] . '/rendez_vous.php'
        );
    }
}
