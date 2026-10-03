<?php
require_once __DIR__ . '/../../includes/init.php';
$user = require_role('client');

if (empty($user['entreprise_id'])) {
    flash('error', 'Votre compte n\'est lié à aucune entreprise. Contactez votre entreprise.');
    redirect('pages/client/dashboard.php');
}

redirect('pages/client/rdv_demande.php?entreprise_id=' . (int) $user['entreprise_id']);
