<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

mb_internal_encoding('UTF-8');
if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/messaging.php';
require_once __DIR__ . '/reminders.php';

date_default_timezone_set('Africa/Kinshasa');

if (is_logged_in()) {
    process_rdv_reminders(current_user());
}