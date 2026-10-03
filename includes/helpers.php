<?php

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    $path = safe_internal_path($path, 'index.php');
    header('Location: ' . APP_BASE . '/' . ltrim($path, '/'));
    exit;
}

/** N’accepte que des chemins internes à l’application (anti open-redirect). */
function safe_internal_path(?string $url, string $fallback = 'index.php'): string
{
    if ($url === null || trim($url) === '') {
        return $fallback;
    }
    $url = trim($url);

    if (preg_match('#^https?://#i', $url)) {
        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));
        $appHost = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        if ($host === '' || $appHost === '' || !hash_equals($appHost, $host)) {
            return $fallback;
        }
        $path = (string) ($parts['path'] ?? '/');
        $base = rtrim((string) APP_BASE, '/');
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base)) ?: '/';
        }
        $rel = ltrim($path, '/');
        if (!empty($parts['query'])) {
            $rel .= '?' . $parts['query'];
        }
        return $rel !== '' ? $rel : $fallback;
    }

    if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $url)) {
        return $fallback;
    }

    return ltrim($url, '/');
}

/** Autorise uniquement http/https pour les liens externes (anti javascript:). */
function sanitize_http_url(?string $url): ?string
{
    $url = trim((string) $url);
    if ($url === '') {
        return null;
    }
    if (!preg_match('#^https?://#i', $url)) {
        $url = 'https://' . $url;
    }
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return null;
    }
    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    if (!in_array($scheme, ['http', 'https'], true)) {
        return null;
    }
    return $url;
}

function initials_from_text(string $text): string
{
    $text = trim($text);
    if ($text === '') {
        return '?';
    }
    if (function_exists('mb_substr') && function_exists('mb_strtoupper')) {
        return mb_strtoupper(mb_substr($text, 0, 1, 'UTF-8'), 'UTF-8');
    }
    return strtoupper(substr($text, 0, 1));
}

function url(string $path = ''): string
{
    return APP_BASE . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return APP_BASE . '/public/' . ltrim($path, '/');
}

function logo_url(): string
{
    return url('assets/plan.jpg');
}

function uploads_dir(string $relative = ''): string
{
    $base = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads';
    $relative = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($relative, '/\\'));
    return $relative === '' ? $base : $base . DIRECTORY_SEPARATOR . $relative;
}

function entreprise_logo_url(?string $logo): ?string
{
    if ($logo === null || $logo === '') {
        return null;
    }
    $safe = basename($logo);
    if ($safe === '' || !is_file(uploads_dir('entreprises/' . $safe))) {
        return null;
    }
    return url('uploads/entreprises/' . $safe);
}

function delete_entreprise_logo_file(?string $logo): void
{
    if ($logo === null || $logo === '') {
        return;
    }
    $path = uploads_dir('entreprises/' . basename($logo));
    if (is_file($path)) {
        @unlink($path);
    }
}

/**
 * Traite l'upload d'un logo entreprise (JPG/PNG/WEBP/GIF, max 2 Mo).
 * @return array{ok:bool,uploaded:bool,path:?string,error:?string}
 */
function handle_entreprise_logo_upload(string $field = 'logo', ?string $oldLogo = null): array
{
    if (empty($_FILES[$field]) || (int) ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'uploaded' => false, 'path' => null, 'error' => null];
    }

    $file = $_FILES[$field];
    if ((int) ($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'uploaded' => false, 'path' => null, 'error' => 'Échec du téléversement de la photo.'];
    }
    if ((int) ($file['size'] ?? 0) > 2 * 1024 * 1024) {
        return ['ok' => false, 'uploaded' => false, 'path' => null, 'error' => 'La photo ne doit pas dépasser 2 Mo.'];
    }

    $mime = null;
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
        }
    }
    if (!$mime && function_exists('mime_content_type')) {
        $mime = mime_content_type($file['tmp_name']);
    }

    $map = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    if (!$mime || !isset($map[$mime])) {
        return ['ok' => false, 'uploaded' => false, 'path' => null, 'error' => 'Formats acceptés : JPG, PNG, WEBP ou GIF.'];
    }

    $dir = uploads_dir('entreprises');
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return ['ok' => false, 'uploaded' => false, 'path' => null, 'error' => 'Dossier uploads inaccessible.'];
    }

    $filename = 'ent_' . bin2hex(random_bytes(8)) . '.' . $map[$mime];
    $dest = $dir . DIRECTORY_SEPARATOR . $filename;
    if (!is_uploaded_file($file['tmp_name']) || !move_uploaded_file($file['tmp_name'], $dest)) {
        return ['ok' => false, 'uploaded' => false, 'path' => null, 'error' => 'Impossible d\'enregistrer la photo.'];
    }

    if ($oldLogo) {
        delete_entreprise_logo_file($oldLogo);
    }

    return ['ok' => true, 'uploaded' => true, 'path' => $filename, 'error' => null];
}

function entreprise_avatar_html(array $entreprise, string $class = 'lp-ent-avatar'): string
{
    $name = (string) ($entreprise['raison_sociale'] ?? 'Entreprise');
    $src = entreprise_logo_url($entreprise['logo'] ?? null);
    if ($src) {
        return '<img class="' . e($class) . ' ' . e($class) . '--img" src="' . e($src) . '" alt="' . e($name) . '" width="48" height="48" loading="lazy">';
    }
    $letter = function_exists('mb_substr')
        ? mb_strtoupper(mb_substr($name, 0, 1))
        : strtoupper(substr($name, 0, 1));
    return '<div class="' . e($class) . '" aria-hidden="true">' . e($letter) . '</div>';
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        flash('error', 'Jeton de sécurité invalide. Veuillez réessayer.');
        redirect(safe_internal_path($_SERVER['HTTP_REFERER'] ?? null, 'index.php'));
    }
}

function post(string $key, $default = null)
{
    return isset($_POST[$key]) ? trim((string) $_POST[$key]) : $default;
}

function get(string $key, $default = null)
{
    return isset($_GET[$key]) ? trim((string) $_GET[$key]) : $default;
}

function format_datetime(?string $dt): string
{
    if (!$dt) {
        return '—';
    }
    $d = DateTime::createFromFormat('Y-m-d H:i:s', $dt) ?: new DateTime($dt);
    return $d->format('d/m/Y H:i');
}

function format_date(?string $dt): string
{
    if (!$dt) {
        return '—';
    }
    $d = new DateTime($dt);
    return $d->format('d/m/Y');
}

function truncate_text(string $text, int $max = 80): string
{
    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($text, 0, $max, '…', 'UTF-8');
    }
    if (strlen($text) <= $max) {
        return $text;
    }
    return substr($text, 0, max(0, $max - 1)) . '…';
}

function user_initials(array $user): string
{
    $p = trim((string) ($user['prenom'] ?? ''));
    $n = trim((string) ($user['nom'] ?? ''));
    $a = $p !== '' ? strtoupper(substr($p, 0, 1)) : '';
    $b = $n !== '' ? strtoupper(substr($n, 0, 1)) : '';
    return $a . $b ?: '?';
}

function statut_label(string $statut): string
{
    $map = [
        'en_attente' => 'En attente',
        'accepte' => 'Confirmé',
        'refuse' => 'Refusé',
        'reporte' => 'Reporté',
        'annule' => 'Annulé',
        'termine' => 'Terminé',
        'a_faire' => 'À faire',
        'en_cours' => 'En cours',
        'terminee' => 'Terminé',
        'annulee' => 'Annulé',
        'basse' => 'Basse',
        'moyenne' => 'Moyenne',
        'haute' => 'Haute',
        'urgente' => 'Urgente',
    ];
    return $map[$statut] ?? $statut;
}

function statut_badge(string $statut): string
{
    // Brief UI: confirmé=vert, attente=orange, annulé=rouge, terminé=bleu
    $class = match ($statut) {
        'accepte', 'terminee', 'termine' => $statut === 'accepte' ? 'badge-success' : 'badge-info',
        'en_attente', 'a_faire', 'reporte', 'moyenne' => 'badge-warning',
        'refuse', 'annule', 'annulee', 'urgente', 'haute' => 'badge-danger',
        'en_cours', 'basse' => 'badge-info',
        default => 'badge-muted',
    };
    if ($statut === 'accepte') {
        $class = 'badge-success';
    }
    if (in_array($statut, ['termine', 'terminee'], true)) {
        $class = 'badge-info';
    }
    if ($statut === 'en_attente') {
        $class = 'badge-warning';
    }
    if (in_array($statut, ['annule', 'annulee', 'refuse'], true)) {
        $class = 'badge-danger';
    }
    return '<span class="badge ' . $class . '">' . e(statut_label($statut)) . '</span>';
}

/**
 * Harmonise statut et progression (0–100).
 * @return array{0:string,1:int} [statut, progression]
 */
function sync_activite_progression(string $statut, int $progression): array
{
    $progression = max(0, min(100, $progression));
    $statuts = ['a_faire', 'en_cours', 'terminee', 'annulee'];
    if (!in_array($statut, $statuts, true)) {
        $statut = 'a_faire';
    }

    if ($statut === 'annulee') {
        return [$statut, $progression];
    }
    if ($statut === 'terminee' || $progression >= 100) {
        return ['terminee', 100];
    }
    if ($progression > 0 && $statut === 'a_faire') {
        return ['en_cours', $progression];
    }
    if ($statut === 'a_faire') {
        return ['a_faire', 0];
    }
    return [$statut, $progression];
}

function progression_bar_html(int $progression, string $size = ''): string
{
    $progression = max(0, min(100, $progression));
    $tone = $progression >= 100 ? 'is-done' : ($progression >= 60 ? 'is-high' : ($progression >= 30 ? 'is-mid' : 'is-low'));
    $sizeClass = $size !== '' ? ' progress-bar--' . $size : '';
    return '<div class="progress-bar' . e($sizeClass) . ' ' . e($tone) . '" role="progressbar" aria-valuenow="' . $progression . '" aria-valuemin="0" aria-valuemax="100" title="' . $progression . ' %">'
        . '<span class="progress-bar__fill" style="width:' . $progression . '%"></span>'
        . '<span class="progress-bar__label">' . $progression . '&nbsp;%</span>'
        . '</div>';
}

function log_historique(?int $userId, string $type, int $entiteId, string $action, ?string $details = null): void
{
    $stmt = db()->prepare('INSERT INTO historique (user_id, entite_type, entite_id, action, details) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$userId, $type, $entiteId, $action, $details]);
}

function notify(int $userId, string $titre, string $message, string $type = 'info', ?string $lien = null): void
{
    $stmt = db()->prepare('INSERT INTO notifications (user_id, titre, message, type, lien) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$userId, $titre, $message, $type, $lien]);
}

function unread_notifications_count(int $userId): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND lu = 0');
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

function get_entreprise_id_for_user(array $user): ?int
{
    if ($user['role'] === 'entreprise') {
        $stmt = db()->prepare('SELECT id FROM entreprises WHERE user_id = ?');
        $stmt->execute([$user['id']]);
        $id = $stmt->fetchColumn();
        return $id ? (int) $id : null;
    }
    if ($user['role'] === 'employe') {
        return $user['entreprise_id'] ? (int) $user['entreprise_id'] : null;
    }
    return null;
}

function parse_datetime_local(?string $value): ?DateTime
{
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }
    $formats = [
        'Y-m-d\TH:i:s',
        'Y-m-d\TH:i',
        'Y-m-d H:i:s',
        'Y-m-d H:i',
        'd/m/Y H:i',
    ];
    foreach ($formats as $format) {
        $dt = DateTime::createFromFormat($format, $value);
        if ($dt instanceof DateTime) {
            $errors = DateTime::getLastErrors();
            if (($errors['warning_count'] ?? 0) === 0 && ($errors['error_count'] ?? 0) === 0) {
                return $dt;
            }
        }
    }
    try {
        return new DateTime($value);
    } catch (Throwable $e) {
        return null;
    }
}

function role_home(string $role): string
{
    return match ($role) {
        'entreprise' => 'pages/entreprise/dashboard.php',
        'employe' => 'pages/employe/dashboard.php',
        'client' => 'pages/client/dashboard.php',
        'partenaire' => 'pages/partenaire/dashboard.php',
        default => 'index.php',
    };
}

/**
 * Crée ou met à jour un compte visiteur avec le mot de passe choisi par l'entreprise.
 * @return array{user:array,created:bool,already:bool}
 */
function create_visiteur_with_password(
    int $entrepriseId,
    string $email,
    string $prenom,
    string $nom,
    string $password,
    ?string $telephone = null,
    ?int $contactPublicId = null
): array {
    $pdo = db();
    $email = strtolower(trim($email));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Email invalide.');
    }
    if (strlen($password) < 6) {
        throw new InvalidArgumentException('Mot de passe trop court (min. 6 caractères).');
    }
    $prenom = trim($prenom) !== '' ? trim($prenom) : 'Visiteur';
    $nom = trim($nom) !== '' ? trim($nom) : 'PLANIPRO';
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $existing = $stmt->fetch();

    if ($existing) {
        if ($existing['role'] === 'entreprise') {
            throw new RuntimeException('Cet email appartient déjà à une entreprise.');
        }
        if ($existing['role'] === 'employe' && (int) ($existing['entreprise_id'] ?? 0) === $entrepriseId) {
            $pdo->prepare('UPDATE users SET prenom = ?, nom = ?, telephone = ?, password_hash = ?, actif = 1 WHERE id = ?')
                ->execute([$prenom, $nom, $telephone, $hash, $existing['id']]);
            if ($contactPublicId) {
                $pdo->prepare('UPDATE contact_public SET expediteur_user_id = ?, mdp_a_envoyer = ?, lu = 1 WHERE id = ? AND entreprise_id = ?')
                    ->execute([$existing['id'], $password, $contactPublicId, $entrepriseId]);
            }
            $user = get_user_by_id((int) $existing['id']);
            return ['user' => $user, 'created' => false, 'already' => true];
        }
        if ($existing['role'] === 'employe' && (int) ($existing['entreprise_id'] ?? 0) !== $entrepriseId) {
            throw new RuntimeException('Cet email est déjà lié à une autre entreprise comme visiteur.');
        }

        if (in_array($existing['role'], ['client', 'partenaire'], true)) {
            throw new RuntimeException('Cet email appartient déjà à un client ou partenaire. Utilisez un autre email pour le visiteur.');
        }

        throw new RuntimeException('Cet email est déjà utilisé.');
    }

    $pdo->beginTransaction();
    try {
        $pdo->prepare('INSERT INTO users (email, password_hash, role, nom, prenom, telephone, entreprise_id, actif) VALUES (?, ?, ?, ?, ?, ?, ?, 1)')
            ->execute([$email, $hash, 'employe', $nom, $prenom, $telephone, $entrepriseId]);
        $newId = (int) $pdo->lastInsertId();
        if ($contactPublicId) {
            $pdo->prepare('UPDATE contact_public SET expediteur_user_id = ?, mdp_a_envoyer = ?, lu = 1 WHERE id = ? AND entreprise_id = ?')
                ->execute([$newId, $password, $contactPublicId, $entrepriseId]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return ['user' => get_user_by_id($newId), 'created' => true, 'already' => false];
}

function public_guest_tokens(): array
{
    $raw = $_COOKIE['planipro_public'] ?? '';
    if ($raw === '') {
        return [];
    }
    $parts = array_filter(array_map('trim', explode(',', $raw)));
    return array_values(array_unique(array_filter($parts, static fn($t) => preg_match('/^[a-f0-9]{32,64}$/i', $t))));
}

function public_guest_remember_token(string $token): void
{
    $tokens = public_guest_tokens();
    if (!in_array($token, $tokens, true)) {
        $tokens[] = $token;
    }
    $tokens = array_slice($tokens, -20);
    setcookie('planipro_public', implode(',', $tokens), [
        'expires' => time() + 60 * 60 * 24 * 90,
        'path' => APP_BASE ?: '/',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    $_COOKIE['planipro_public'] = implode(',', $tokens);
}

function unread_public_notifications_count(?array $tokens = null): int
{
    $tokens = $tokens ?? public_guest_tokens();
    if (!$tokens) {
        return 0;
    }
    try {
        $in = implode(',', array_fill(0, count($tokens), '?'));
        $stmt = db()->prepare("SELECT COUNT(*) FROM notifications_publiques WHERE public_token IN ($in) AND lu = 0");
        $stmt->execute($tokens);
        return (int) $stmt->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

function list_public_notifications(array $tokens): array
{
    if (!$tokens) {
        return [];
    }
    $in = implode(',', array_fill(0, count($tokens), '?'));
    $stmt = db()->prepare("
        SELECT n.*, e.raison_sociale, e.logo
        FROM notifications_publiques n
        JOIN entreprises e ON e.id = n.entreprise_id
        WHERE n.public_token IN ($in)
        ORDER BY n.created_at DESC
        LIMIT 100
    ");
    $stmt->execute($tokens);
    return $stmt->fetchAll();
}

function rdv_status_for_contact_email(int $entrepriseId, string $email): ?array
{
    $stmt = db()->prepare("
        SELECT r.*
        FROM rendez_vous r
        JOIN users u ON u.id = r.demandeur_id
        WHERE r.entreprise_id = ? AND u.email = ?
        ORDER BY r.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$entrepriseId, strtolower($email)]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function build_public_visitor_notification_message(array $contact, array $entreprise, ?array $visiteur, ?string $password, ?array $rdv): string
{
    $lines = [];
    $lines[] = 'Bonjour ' . $contact['nom'] . ',';
    $lines[] = '';
    $lines[] = 'Message concernant ' . ($entreprise['raison_sociale'] ?? 'l’entreprise') . '.';

    if ($rdv) {
        $lines[] = '';
        $lines[] = 'Statut de votre rendez-vous « ' . $rdv['titre'] . ' » : ' . statut_label($rdv['statut']) . '.';
        if ($rdv['statut'] === 'accepte') {
            $lines[] = 'Date : ' . format_datetime($rdv['date_debut']);
        }
        if (in_array($rdv['statut'], ['refuse', 'annule'], true)) {
            $lines[] = 'Votre demande n’a pas pu être confirmée.';
        }
    } else {
        $lines[] = '';
        $lines[] = 'Aucun rendez-vous enregistré pour le moment avec cet email.';
    }

    if ($visiteur && $password) {
        $lines[] = '';
        $lines[] = 'Vos coordonnées de connexion (espace visiteur) :';
        $lines[] = 'Email : ' . $visiteur['email'];
        $lines[] = 'Mot de passe (affichage unique) : ' . $password;
        $lines[] = 'Connectez-vous sur PLANIPRO. Conservez ce mot de passe : il sera masqué après lecture.';
    } elseif ($visiteur) {
        $lines[] = '';
        $lines[] = 'Un compte visiteur existe pour ' . $visiteur['email'] . '. Utilisez le mot de passe communiqué par l’entreprise.';
    }

    return implode("\n", $lines);
}

function send_public_visitor_notification(array $contact, string $titre = 'Mise à jour de votre demande'): int
{
    if (empty($contact['public_token'])) {
        throw new RuntimeException('Jeton public manquant pour ce contact.');
    }
    $entreprise = get_entreprise_publique((int) $contact['entreprise_id']);
    if (!$entreprise) {
        throw new RuntimeException('Entreprise introuvable.');
    }

    $visiteur = !empty($contact['expediteur_user_id'])
        ? get_user_by_id((int) $contact['expediteur_user_id'])
        : null;
    $password = $contact['mdp_a_envoyer'] ?? null;
    $rdv = rdv_status_for_contact_email((int) $contact['entreprise_id'], (string) $contact['email']);
    $message = build_public_visitor_notification_message($contact, $entreprise, $visiteur, $password, $rdv);

    $type = 'info';
    if ($rdv) {
        $type = match ($rdv['statut']) {
            'accepte' => 'success',
            'refuse', 'annule' => 'warning',
            default => 'info',
        };
    }

    $pdo = db();
    $pdo->prepare('INSERT INTO notifications_publiques (public_token, contact_public_id, entreprise_id, titre, message, type) VALUES (?, ?, ?, ?, ?, ?)')
        ->execute([
            $contact['public_token'],
            (int) $contact['id'],
            (int) $contact['entreprise_id'],
            $titre,
            $message,
            $type,
        ]);
    $id = (int) $pdo->lastInsertId();
    $pdo->prepare('UPDATE contact_public SET notif_envoyee = 1, mdp_a_envoyer = NULL WHERE id = ?')
        ->execute([(int) $contact['id']]);
    return $id;
}

function contact_public_by_visiteur(int $entrepriseId, int $userId): ?array
{
    $stmt = db()->prepare('SELECT * FROM contact_public WHERE entreprise_id = ? AND expediteur_user_id = ? ORDER BY created_at DESC LIMIT 1');
    $stmt->execute([$entrepriseId, $userId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

