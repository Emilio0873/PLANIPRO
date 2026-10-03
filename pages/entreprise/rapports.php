<?php
require_once __DIR__ . '/../../includes/init.php';
$user = require_role('entreprise');
$entrepriseId = get_entreprise_id_for_user($user);
$type = get('type', 'rdv');
$pdo = db();

$pageTitle = 'Rapports';
require_once __DIR__ . '/../../includes/header.php';

$types = [
    'rdv' => 'Rendez-vous',
    'activites' => 'Activités',
    'clients' => 'Clients',
    'partenaires' => 'Partenaires',
];
?>
<div class="card">
    <div class="card-header">
        <h2>Rapports de gestion</h2>
        <div class="toolbar-actions">
            <a class="btn btn-sm btn-secondary" href="<?= url('actions/rapport_export.php?type=' . urlencode($type)) ?>" target="_blank">Exporter PDF / Imprimer</a>
        </div>
    </div>

    <form class="filters filters--simple" method="get">
        <div class="form-group">
            <label for="type">Type de rapport</label>
            <select name="type" id="type" onchange="this.form.submit()">
                <?php foreach ($types as $k => $label): ?>
                    <option value="<?= $k ?>" <?= $type === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <noscript><button class="btn btn-primary" type="submit">Afficher</button></noscript>
    </form>

    <?php if ($type === 'rdv'):
        $rows = $pdo->prepare("SELECT r.*, u.prenom, u.nom FROM rendez_vous r JOIN users u ON u.id = r.demandeur_id WHERE r.entreprise_id = ? ORDER BY r.date_debut DESC");
        $rows->execute([$entrepriseId]);
        $data = $rows->fetchAll();
        $counts = $pdo->prepare("SELECT statut, COUNT(*) c FROM rendez_vous WHERE entreprise_id = ? GROUP BY statut");
        $counts->execute([$entrepriseId]);
        $byStatut = $counts->fetchAll();
    ?>
        <div class="stats-grid">
            <?php
            $statColors = [
                'en_attente' => 'stat-amber',
                'accepte' => 'stat-green',
                'refuse' => 'stat-rose',
                'reporte' => 'stat-sky',
                'annule' => 'stat-slate',
                'termine' => 'stat-blue',
            ];
            foreach ($byStatut as $c):
                $color = $statColors[$c['statut']] ?? 'stat-indigo';
            ?>
                <div class="stat-card <?= e($color) ?>"><div class="label"><?= e(statut_label($c['statut'])) ?></div><div class="value"><?= (int)$c['c'] ?></div></div>
            <?php endforeach; ?>
        </div>
        <div class="table-wrap"><table class="data">
            <thead><tr><th>Titre</th><th>Demandeur</th><th>Type</th><th>Début</th><th>Statut</th></tr></thead>
            <tbody>
            <?php foreach ($data as $r): ?>
                <tr>
                    <td><?= e($r['titre']) ?></td>
                    <td><?= e($r['prenom'].' '.$r['nom']) ?></td>
                    <td><?= e($r['type_demandeur']) ?></td>
                    <td><?= e(format_datetime($r['date_debut'])) ?></td>
                    <td><?= statut_badge($r['statut']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$data): ?><tr><td colspan="5" class="empty">Aucune donnée</td></tr><?php endif; ?>
            </tbody>
        </table></div>

    <?php elseif ($type === 'activites'):
        $rows = $pdo->prepare("SELECT a.*, u.prenom, u.nom FROM activites a LEFT JOIN users u ON u.id = a.assigne_a WHERE a.entreprise_id = ? ORDER BY a.created_at DESC");
        $rows->execute([$entrepriseId]);
        $data = $rows->fetchAll();
    ?>
        <div class="table-wrap"><table class="data">
            <thead><tr><th>Titre</th><th>Priorité</th><th>Assigné</th><th>Échéance</th><th>Progression</th><th>Statut</th></tr></thead>
            <tbody>
            <?php foreach ($data as $a): ?>
                <tr>
                    <td><?= e($a['titre']) ?></td>
                    <td><?= statut_badge($a['priorite']) ?></td>
                    <td><?= e($a['prenom'] ? $a['prenom'].' '.$a['nom'] : '—') ?></td>
                    <td><?= e(format_date($a['echeance'])) ?></td>
                    <td style="min-width:120px"><?= progression_bar_html((int) ($a['progression'] ?? 0), 'sm') ?></td>
                    <td><?= statut_badge($a['statut']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$data): ?><tr><td colspan="6" class="empty">Aucune donnée</td></tr><?php endif; ?>
            </tbody>
        </table></div>

    <?php elseif ($type === 'clients'):
        $rows = $pdo->prepare("
            SELECT u.prenom, u.nom, u.email, u.telephone, COUNT(r.id) nb
            FROM rendez_vous r JOIN users u ON u.id = r.demandeur_id
            WHERE r.entreprise_id = ? AND r.type_demandeur = 'client'
            GROUP BY u.id ORDER BY nb DESC
        ");
        $rows->execute([$entrepriseId]);
        $data = $rows->fetchAll();
    ?>
        <div class="table-wrap"><table class="data">
            <thead><tr><th>Nom</th><th>Email</th><th>Téléphone</th><th>Nb RDV</th></tr></thead>
            <tbody>
            <?php foreach ($data as $c): ?>
                <tr>
                    <td><?= e($c['prenom'].' '.$c['nom']) ?></td>
                    <td><?= e($c['email']) ?></td>
                    <td><?= e($c['telephone'] ?: '—') ?></td>
                    <td><?= (int)$c['nb'] ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$data): ?><tr><td colspan="4" class="empty">Aucune donnée</td></tr><?php endif; ?>
            </tbody>
        </table></div>

    <?php else:
        $rows = $pdo->prepare("
            SELECT u.prenom, u.nom, u.email, p.organisation, COUNT(r.id) nb
            FROM rendez_vous r
            JOIN users u ON u.id = r.demandeur_id
            LEFT JOIN partenaires p ON p.user_id = u.id
            WHERE r.entreprise_id = ? AND r.type_demandeur = 'partenaire'
            GROUP BY u.id ORDER BY nb DESC
        ");
        $rows->execute([$entrepriseId]);
        $data = $rows->fetchAll();
    ?>
        <div class="table-wrap"><table class="data">
            <thead><tr><th>Nom</th><th>Organisation</th><th>Email</th><th>Nb RDV</th></tr></thead>
            <tbody>
            <?php foreach ($data as $p): ?>
                <tr>
                    <td><?= e($p['prenom'].' '.$p['nom']) ?></td>
                    <td><?= e($p['organisation'] ?: '—') ?></td>
                    <td><?= e($p['email']) ?></td>
                    <td><?= (int)$p['nb'] ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$data): ?><tr><td colspan="4" class="empty">Aucune donnée</td></tr><?php endif; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
