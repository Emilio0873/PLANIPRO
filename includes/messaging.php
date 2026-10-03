<?php

function user_entreprise_context(array $user): ?int
{
    if ($user['role'] === 'entreprise') {
        return get_entreprise_id_for_user($user);
    }
    return !empty($user['entreprise_id']) ? (int) $user['entreprise_id'] : null;
}

function get_user_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ? AND actif = 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function users_share_entreprise(array $a, array $b): bool
{
    $ea = user_entreprise_context($a);
    $eb = user_entreprise_context($b);
    return $ea && $eb && $ea === $eb;
}

function can_message_user(array $from, int $toId): bool
{
    if ((int) $from['id'] === $toId) {
        return false;
    }
    $to = get_user_by_id($toId);
    if (!$to) {
        return false;
    }
    // Même entreprise (interne)
    if (users_share_entreprise($from, $to)) {
        return true;
    }
    // Tout utilisateur connecté peut écrire au compte d'une entreprise
    if ($to['role'] === 'entreprise') {
        return true;
    }
    // Entreprise : uniquement utilisateurs déjà liés (contact public ou conversation)
    if ($from['role'] === 'entreprise') {
        $eid = user_entreprise_context($from);
        if (!$eid) {
            return false;
        }
        try {
            $stmt = db()->prepare('
                SELECT 1 FROM contact_public
                WHERE entreprise_id = ? AND (expediteur_user_id = ? OR LOWER(email) = LOWER(?))
                LIMIT 1
            ');
            $stmt->execute([$eid, $toId, (string) $to['email']]);
            if ($stmt->fetchColumn()) {
                return true;
            }
        } catch (Throwable $e) {
            // table absente
        }
        $a = min((int) $from['id'], $toId);
        $b = max((int) $from['id'], $toId);
        $conv = db()->prepare('SELECT 1 FROM conversations WHERE participant_a = ? AND participant_b = ? LIMIT 1');
        $conv->execute([$a, $b]);
        return (bool) $conv->fetchColumn();
    }
    return false;
}

function list_entreprises_publiques(?string $q = null): array
{
    $sql = "
        SELECT e.*, u.id AS owner_user_id, u.prenom, u.nom, u.email, u.telephone
        FROM entreprises e
        JOIN users u ON u.id = e.user_id
        WHERE u.actif = 1
    ";
    $params = [];
    if ($q !== null && trim($q) !== '') {
        $sql .= " AND (e.raison_sociale LIKE ? OR e.secteur LIKE ? OR e.adresse LIKE ? OR e.description LIKE ?)";
        $like = '%' . trim($q) . '%';
        $params = [$like, $like, $like, $like];
    }
    $sql .= ' ORDER BY e.raison_sociale ASC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_entreprise_publique(int $id): ?array
{
    $stmt = db()->prepare("
        SELECT e.*, u.id AS owner_user_id, u.prenom, u.nom, u.email, u.telephone
        FROM entreprises e
        JOIN users u ON u.id = e.user_id
        WHERE e.id = ? AND u.actif = 1
    ");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function unread_contact_public_count(int $entrepriseId): int
{
    try {
        $stmt = db()->prepare('SELECT COUNT(*) FROM contact_public WHERE entreprise_id = ? AND lu = 0');
        $stmt->execute([$entrepriseId]);
        return (int) $stmt->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

function messagerie_contacts(array $user): array
{
    $list = [];
    $entrepriseId = user_entreprise_context($user);

    if ($entrepriseId) {
        // Propriétaire entreprise
        $owner = db()->prepare('SELECT u.* FROM entreprises e JOIN users u ON u.id = e.user_id WHERE e.id = ? AND u.actif = 1');
        $owner->execute([$entrepriseId]);
        $ownerUser = $owner->fetch();

        // Membres liés (employés, clients, partenaires)
        $members = db()->prepare("SELECT * FROM users WHERE entreprise_id = ? AND actif = 1 AND id != ? ORDER BY role, nom, prenom");
        $members->execute([$entrepriseId, $user['id']]);
        $list = $members->fetchAll();

        if ($ownerUser && (int) $ownerUser['id'] !== (int) $user['id']) {
            array_unshift($list, $ownerUser);
        }
    }

    // Toutes les entreprises (comptes propriétaires) pour écrire depuis n'importe quel profil
    // sauf depuis un compte entreprise (évite le spam B2B non lié)
    if ($user['role'] !== 'entreprise') {
        $entreprises = db()->prepare("
            SELECT u.*
            FROM users u
            JOIN entreprises e ON e.user_id = u.id
            WHERE u.actif = 1 AND u.role = 'entreprise' AND u.id != ?
            ORDER BY e.raison_sociale, u.nom
        ");
        $entreprises->execute([(int) $user['id']]);
        foreach ($entreprises->fetchAll() as $row) {
            $list[] = $row;
        }
    }

    // Dédupliquer
    $seen = [];
    $out = [];
    foreach ($list as $row) {
        $id = (int) $row['id'];
        if (isset($seen[$id])) {
            continue;
        }
        $seen[$id] = true;
        $out[] = $row;
    }
    return $out;
}

function find_or_create_conversation(int $userA, int $userB): int
{
    $a = min($userA, $userB);
    $b = max($userA, $userB);
    $stmt = db()->prepare('SELECT id FROM conversations WHERE participant_a = ? AND participant_b = ?');
    $stmt->execute([$a, $b]);
    $id = $stmt->fetchColumn();
    if ($id) {
        return (int) $id;
    }
    db()->prepare('INSERT INTO conversations (participant_a, participant_b) VALUES (?, ?)')->execute([$a, $b]);
    return (int) db()->lastInsertId();
}

function conversation_other_user(array $conversation, int $meId): ?array
{
    $otherId = (int) $conversation['participant_a'] === $meId
        ? (int) $conversation['participant_b']
        : (int) $conversation['participant_a'];
    return get_user_by_id($otherId);
}

function user_conversations(int $userId): array
{
    $sql = "
        SELECT c.*,
               (SELECT body FROM messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC, m.id DESC LIMIT 1) AS last_body,
               (SELECT created_at FROM messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC, m.id DESC LIMIT 1) AS last_at,
               (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id AND m.sender_id != ? AND m.lu = 0) AS unread
        FROM conversations c
        WHERE c.participant_a = ? OR c.participant_b = ?
        ORDER BY COALESCE(c.last_message_at, c.created_at) DESC
    ";
    $stmt = db()->prepare($sql);
    $stmt->execute([$userId, $userId, $userId]);
    return $stmt->fetchAll();
}

function conversation_messages(int $conversationId): array
{
    $stmt = db()->prepare("
        SELECT m.*, u.prenom, u.nom, u.role
        FROM messages m
        JOIN users u ON u.id = m.sender_id
        WHERE m.conversation_id = ?
        ORDER BY m.created_at ASC, m.id ASC
    ");
    $stmt->execute([$conversationId]);
    return $stmt->fetchAll();
}

function mark_conversation_read(int $conversationId, int $userId): void
{
    $stmt = db()->prepare('UPDATE messages SET lu = 1 WHERE conversation_id = ? AND sender_id != ? AND lu = 0');
    $stmt->execute([$conversationId, $userId]);
}

function unread_messages_count(int $userId): int
{
    try {
        $stmt = db()->prepare("
            SELECT COUNT(*) FROM messages m
            JOIN conversations c ON c.id = m.conversation_id
            WHERE m.lu = 0 AND m.sender_id != ?
              AND (c.participant_a = ? OR c.participant_b = ?)
        ");
        $stmt->execute([$userId, $userId, $userId]);
        return (int) $stmt->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

function send_message(int $conversationId, int $senderId, string $body): bool
{
    $body = trim($body);
    if ($body === '') {
        return false;
    }
    $pdo = db();
    $pdo->prepare('INSERT INTO messages (conversation_id, sender_id, body) VALUES (?, ?, ?)')
        ->execute([$conversationId, $senderId, $body]);
    $pdo->prepare('UPDATE conversations SET last_message_at = NOW() WHERE id = ?')
        ->execute([$conversationId]);
    return true;
}

function user_in_conversation(array $conversation, int $userId): bool
{
    return (int) $conversation['participant_a'] === $userId
        || (int) $conversation['participant_b'] === $userId;
}
