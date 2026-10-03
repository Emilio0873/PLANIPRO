<?php
require_once __DIR__ . '/../../includes/init.php';
$user = require_role('client');
$stmt = db()->prepare("SELECT r.*, e.raison_sociale FROM rendez_vous r JOIN entreprises e ON e.id = r.entreprise_id WHERE r.demandeur_id = ? AND r.statut IN ('termine','annule','refuse') ORDER BY r.date_debut DESC");
$stmt->execute([$user['id']]);
$rdvs = $stmt->fetchAll();
$pageTitle = 'Historique';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="card">
    <?php if (!$rdvs): ?>
        <div class="empty">Aucun historique.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Titre</th><th>Entreprise</th><th>Date</th><th>Statut</th></tr></thead>
                <tbody>
                <?php foreach ($rdvs as $r): ?>
                    <tr>
                        <td><?= e($r['titre']) ?></td>
                        <td><?= e($r['raison_sociale']) ?></td>
                        <td><?= e(format_datetime($r['date_debut'])) ?></td>
                        <td><?= statut_badge($r['statut']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
