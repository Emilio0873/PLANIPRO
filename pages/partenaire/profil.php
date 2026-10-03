<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/parametres_ui.php';

$user = require_role('partenaire');
$stmt = db()->prepare('SELECT * FROM partenaires WHERE user_id = ?');
$stmt->execute([$user['id']]);
$extra = $stmt->fetch() ?: [];

$pageTitle = 'Paramètres';
require_once __DIR__ . '/../../includes/header.php';
render_parametres($user, $extra);
require_once __DIR__ . '/../../includes/footer.php';
