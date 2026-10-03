<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/parametres_ui.php';

$user = require_role('employe');
$entrepriseContext = null;
if (!empty($user['entreprise_id'])) {
    $stmt = db()->prepare('SELECT * FROM entreprises WHERE id = ?');
    $stmt->execute([(int) $user['entreprise_id']]);
    $entrepriseContext = $stmt->fetch() ?: null;
}

$pageTitle = 'Paramètres';
require_once __DIR__ . '/../../includes/header.php';
render_parametres($user, null, $entrepriseContext);
require_once __DIR__ . '/../../includes/footer.php';
