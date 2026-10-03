<?php
/** @deprecated Utiliser parametres_ui.php */
require_once __DIR__ . '/parametres_ui.php';

function render_profil_form(array $user, ?array $extra = null): void
{
    render_parametres($user, $extra);
}
