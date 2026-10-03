<?php
$pageTitle = $pageTitle ?? APP_NAME;
$user = current_user();
$flashes = get_flashes();
$notifCount = $user ? unread_notifications_count((int) $user['id']) : 0;
$msgCount = $user ? unread_messages_count((int) $user['id']) : 0;
$navRole = $user['role'] ?? null;
$currentPath = $_SERVER['SCRIPT_NAME'] ?? '';
$bellIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5"/><path d="M9.5 17a2.5 2.5 0 0 0 5 0"/></svg>';

function nav_active(string $needle): string
{
    global $currentPath;
    return str_contains(str_replace('\\', '/', $currentPath), $needle) ? ' is-active' : '';
}

function nav_ico(string $name): string
{
    $icons = [
        'home' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/></svg>',
        'rdv' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></svg>',
        'tasks' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 6h11M9 12h11M9 18h11"/><path d="m4 6 1.5 1.5L8 5M4 12l1.5 1.5L8 11M4 18l1.5 1.5L8 17"/></svg>',
        'users' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 19a6.5 6.5 0 0 1 13 0"/><circle cx="17" cy="9" r="2.5"/><path d="M16 19a5 5 0 0 1 5.5-4.8"/></svg>',
        'contacts' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="3" width="14" height="18" rx="2"/><circle cx="12" cy="10" r="2.5"/><path d="M8.5 16.5c1.2-1.5 2.3-2 3.5-2s2.3.5 3.5 2"/></svg>',
        'calendar' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M3 9h18M8 2v4M16 2v4M8 14h3M13 14h3M8 18h3"/></svg>',
        'reports' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19V5M4 19h16"/><path d="M8 16V10M12 16V7M16 16v-4"/></svg>',
        'chat' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 6a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v9a3 3 0 0 1-3 3H9l-5 4V6z"/></svg>',
        'inbox' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 7.5 12 13l9-5.5"/><path d="M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2z"/></svg>',
        'settings' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M12 2.5v2.2M12 19.3v2.2M4.9 4.9l1.6 1.6M17.5 17.5l1.6 1.6M2.5 12h2.2M19.3 12h2.2M4.9 19.1l1.6-1.6M17.5 6.5l1.6-1.6"/></svg>',
        'bell' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5"/><path d="M9.5 17a2.5 2.5 0 0 0 5 0"/></svg>',
        'logout' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M10 17H5a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h5"/><path d="M14 12H4"/><path d="m15 8 4 4-4 4"/></svg>',
    ];
    return $icons[$name] ?? $icons['home'];
}

function nav_link(string $href, string $label, string $icon, string $activeNeedle, int $badge = 0): void
{
    $active = '';
    foreach (explode('|', $activeNeedle) as $n) {
        $n = trim($n);
        if ($n !== '' && nav_active($n) !== '') {
            $active = ' is-active';
            break;
        }
    }
    ?>
    <a href="<?= url($href) ?>" class="nav-link<?= $active ?>" title="<?= e($label) ?>">
        <span class="nav-label"><?= e($label) ?></span>
        <?php if ($badge > 0): ?><span class="nav-badge"><?= (int) $badge ?></span><?php endif; ?>
    </a>
    <?php
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="description" content="PLANIPRO — gestion des rendez-vous et activités.">
    <title><?= e($pageTitle) ?> — <?= e(APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>?v=24">
    <?php if (!empty($extraHead)) echo $extraHead; ?>
</head>
<body class="<?= $user ? 'app-body' : 'guest-body' ?><?= !empty($bodyExtra) ? ' ' . e($bodyExtra) : '' ?>">
<?php if ($user): ?>
<div class="app-shell">
    <aside class="sidebar" id="sidebar" aria-label="Navigation">
        <div class="sidebar-brand">
            <a href="<?= url(role_home($user['role'])) ?>" class="brand-link" title="<?= e(APP_NAME) ?>">
                <img src="<?= e(logo_url()) ?>" alt="<?= e(APP_NAME) ?>" class="brand-logo brand-logo--sidebar">
            </a>
            <button type="button" class="sidebar-close" id="sidebarClose" aria-label="Fermer le menu">×</button>
        </div>
        <nav class="sidebar-nav">
            <?php if ($navRole === 'entreprise'): ?>
                <?php
                nav_link('pages/entreprise/dashboard.php', 'Tableau de bord', 'home', '/entreprise/dashboard');
                nav_link('pages/entreprise/rendez_vous.php', 'Rendez-vous', 'rdv', '/entreprise/rendez_vous');
                nav_link('pages/entreprise/activites.php', 'Activités', 'tasks', '/entreprise/activites');
                nav_link('pages/entreprise/employes.php', 'Visiteurs', 'users', '/entreprise/employe');
                nav_link('pages/entreprise/contacts.php', 'Contacts', 'contacts', '/entreprise/contacts|/entreprise/clients|/entreprise/partenaires');
                nav_link('pages/entreprise/calendrier.php', 'Calendrier', 'calendar', '/entreprise/calendrier');
                nav_link('pages/entreprise/rapports.php', 'Rapports', 'reports', '/entreprise/rapports');
                nav_link('pages/messagerie/index.php', 'Messagerie', 'chat', '/messagerie', $msgCount);
                $eid = get_entreprise_id_for_user($user);
                $pubCount = $eid ? unread_contact_public_count($eid) : 0;
                nav_link('pages/entreprise/messages_recus.php', 'Messages publics', 'inbox', '/messages_recus', $pubCount);
                nav_link('pages/entreprise/profil.php', 'Paramètres', 'settings', '/entreprise/profil');
                ?>
            <?php elseif ($navRole === 'employe'): ?>
                <?php
                nav_link('pages/employe/dashboard.php', 'Tableau de bord', 'home', '/employe/dashboard');
                nav_link('pages/employe/activites.php', 'Mes activités', 'tasks', '/employe/activites');
                nav_link('pages/employe/rendez_vous.php', 'Rendez-vous', 'rdv', '/employe/rendez_vous');
                nav_link('pages/employe/calendrier.php', 'Calendrier', 'calendar', '/employe/calendrier');
                nav_link('pages/messagerie/index.php', 'Messagerie', 'chat', '/messagerie', $msgCount);
                nav_link('pages/employe/profil.php', 'Paramètres', 'settings', '/employe/profil');
                ?>
            <?php elseif ($navRole === 'client'): ?>
                <?php
                nav_link('pages/client/dashboard.php', 'Tableau de bord', 'home', '/client/dashboard');
                nav_link('pages/client/entreprises.php', 'Demander un RDV', 'rdv', '/client/rdv_demande|/client/entreprises');
                nav_link('pages/client/rendez_vous.php', 'Mes rendez-vous', 'calendar', '/client/rendez_vous');
                nav_link('pages/client/historique.php', 'Historique', 'reports', '/client/historique');
                nav_link('pages/messagerie/index.php', 'Messagerie', 'chat', '/messagerie', $msgCount);
                nav_link('pages/client/profil.php', 'Paramètres', 'settings', '/client/profil');
                ?>
            <?php elseif ($navRole === 'partenaire'): ?>
                <?php
                nav_link('pages/partenaire/dashboard.php', 'Tableau de bord', 'home', '/partenaire/dashboard');
                nav_link('pages/partenaire/entreprises.php', 'Demander un RDV', 'rdv', '/partenaire/rdv_demande|/partenaire/entreprises');
                nav_link('pages/partenaire/rendez_vous.php', 'Mes rendez-vous', 'calendar', '/partenaire/rendez_vous');
                nav_link('pages/partenaire/historique.php', 'Collaborations', 'reports', '/partenaire/historique');
                nav_link('pages/messagerie/index.php', 'Messagerie', 'chat', '/messagerie', $msgCount);
                nav_link('pages/partenaire/profil.php', 'Paramètres', 'settings', '/partenaire/profil');
                ?>
            <?php endif; ?>
            <?php nav_link('pages/notifications.php', 'Notifications', 'bell', '/notifications', $notifCount); ?>
        </nav>
        <div class="sidebar-footer">
            <div class="user-chip" title="<?= e($user['prenom'] . ' ' . $user['nom'] . ' (' . $user['role'] . ')') ?>">
                <span class="user-chip__avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($user['prenom'], 0, 1) . mb_substr($user['nom'], 0, 1))) ?></span>
                <span class="user-chip__text">
                    <strong><?= e($user['prenom'] . ' ' . $user['nom']) ?></strong>
                    <span><?= e($user['role']) ?></span>
                </span>
            </div>
            <a class="nav-link nav-link--logout" href="<?= url('actions/logout.php') ?>">
                <span class="nav-label">Déconnexion</span>
            </a>
        </div>
    </aside>
    <button type="button" class="sidebar-backdrop" id="sidebarBackdrop" aria-label="Fermer le menu"></button>
    <div class="main-wrap">
        <header class="topbar">
            <button type="button" class="menu-toggle" id="menuToggle" aria-label="Menu" aria-expanded="false">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>
            <h1 class="page-heading"><?= e($pageTitle) ?></h1>
            <a class="topbar-notif" href="<?= url('pages/notifications.php') ?>" title="Notifications" aria-label="Notifications">
                <?= $bellIcon ?>
                <?php if ($notifCount > 0): ?><span class="dot"><?= (int) $notifCount ?></span><?php endif; ?>
            </a>
        </header>
        <main class="content">
            <?php foreach ($flashes as $f): ?>
                <div class="alert alert-<?= e($f['type']) ?>" role="alert"><?= e($f['message']) ?></div>
            <?php endforeach; ?>
<?php else: ?>
<script>location.href=<?= json_encode(url('index.php')) ?>;</script>
<?php endif; ?>
