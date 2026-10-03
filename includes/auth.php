<?php

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    static $cachedId = null;
    static $user = null;

    $id = (int) $_SESSION['user_id'];
    if ($cachedId !== $id || $user === null) {
        $stmt = db()->prepare('SELECT * FROM users WHERE id = ? AND actif = 1');
        $stmt->execute([$id]);
        $user = $stmt->fetch() ?: null;
        $cachedId = $id;
        if (!$user) {
            unset($_SESSION['user_id']);
            $cachedId = null;
        }
    }
    return $user;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        flash('error', 'Veuillez vous connecter pour continuer.');
        redirect('pages/auth/login.php');
    }
    return $user;
}

function require_role(string|array $roles): array
{
    $user = require_login();
    $roles = (array) $roles;
    if (!in_array($user['role'], $roles, true)) {
        flash('error', 'Accès non autorisé.');
        redirect(role_home($user['role']));
    }
    return $user;
}

function attempt_login(string $email, string $password): bool
{
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? AND actif = 1 LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }
    $_SESSION['user_id'] = (int) $user['id'];
    session_regenerate_id(true);
    return true;
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
