<?php
require_once __DIR__ . '/../../includes/init.php';
$user = require_login();

$convId = (int) get('id', 0);
$stmt = db()->prepare('SELECT * FROM conversations WHERE id = ?');
$stmt->execute([$convId]);
$conv = $stmt->fetch();

if (!$conv || !user_in_conversation($conv, (int) $user['id'])) {
    flash('error', 'Conversation introuvable.');
    redirect('pages/messagerie/index.php');
}

$other = conversation_other_user($conv, (int) $user['id']);
if (!$other || !can_message_user($user, (int) $other['id'])) {
    flash('error', 'Accès non autorisé.');
    redirect('pages/messagerie/index.php');
}

mark_conversation_read($convId, (int) $user['id']);
$messages = conversation_messages($convId);
$conversations = user_conversations((int) $user['id']);
$activeConvId = $convId;

$roleLabels = [
    'entreprise' => 'Entreprise',
    'employe' => 'Visiteur',
    'client' => 'Client',
    'partenaire' => 'Partenaire',
];

$pageTitle = $other['prenom'] . ' ' . $other['nom'];
$bodyExtra = 'is-messaging';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="msg-app has-chat-open wa-skin">
    <aside class="msg-sidebar">
        <?php require __DIR__ . '/../../includes/messagerie_list.php'; ?>
    </aside>

    <section class="msg-panel msg-chat">
        <div class="msg-panel-head msg-chat-header">
            <a class="msg-back" href="<?= url('pages/messagerie/index.php') ?>" aria-label="Retour">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 6-6 6 6 6"/></svg>
            </a>
            <div class="msg-avatar" aria-hidden="true"><?= e(user_initials($other)) ?></div>
            <div class="msg-chat-peer">
                <strong><?= e($other['prenom'] . ' ' . $other['nom']) ?></strong>
                <span><?= e($roleLabels[$other['role']] ?? $other['role']) ?></span>
            </div>
        </div>

        <div class="msg-stream" id="msgStream">
            <?php if (!$messages): ?>
                <div class="wa-day-chip">Démarrez la conversation</div>
            <?php else: ?>
                <?php
                $lastDay = '';
                foreach ($messages as $m):
                    $mine = (int) $m['sender_id'] === (int) $user['id'];
                    $dayKey = substr((string) $m['created_at'], 0, 10);
                    if ($dayKey !== $lastDay):
                        $lastDay = $dayKey;
                        ?>
                        <div class="wa-day-chip"><?= e(format_date($m['created_at'])) ?></div>
                    <?php endif; ?>
                    <div class="msg-bubble<?= $mine ? ' is-mine' : ' is-theirs' ?>">
                        <div class="msg-bubble-text"><?= nl2br(e($m['body'])) ?></div>
                        <div class="msg-bubble-time"><?= e(date('H:i', strtotime($m['created_at']))) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <form method="post" action="<?= url('actions/message_send.php') ?>" class="msg-send-form wa-composer">
            <?= csrf_field() ?>
            <input type="hidden" name="conversation_id" value="<?= (int) $convId ?>">
            <textarea name="body" rows="1" required placeholder="Écrire un message" autofocus></textarea>
            <button type="submit" class="wa-send" title="Envoyer" aria-label="Envoyer">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor" aria-hidden="true"><path d="M3.4 20.6 21 12 3.4 3.4 3 10.3 15 12 3 13.7z"/></svg>
            </button>
        </form>
    </section>
</div>
<script>
(function () {
    var el = document.getElementById('msgStream');
    if (el) el.scrollTop = el.scrollHeight;
    var ta = document.querySelector('.wa-composer textarea');
    if (ta) {
        var resize = function () {
            ta.style.height = 'auto';
            ta.style.height = Math.min(120, ta.scrollHeight) + 'px';
        };
        ta.addEventListener('input', resize);
        resize();
    }
})();
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
