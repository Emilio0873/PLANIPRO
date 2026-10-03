<?php
/** Attendu : $conversations, $user, $roleLabels, $activeConvId (int|null) */
$activeConvId = $activeConvId ?? null;
$roleLabels = $roleLabels ?? [
    'entreprise' => 'Entreprise',
    'employe' => 'Visiteur',
    'client' => 'Client',
    'partenaire' => 'Partenaire',
];
?>
<div class="wa-side-head">
    <div class="wa-side-head__user">
        <div class="msg-avatar msg-avatar--self" aria-hidden="true"><?= e(user_initials($user)) ?></div>
        <strong>Discussions</strong>
    </div>
    <a class="wa-new-btn" href="<?= url('pages/messagerie/index.php?nouveau=1') ?>" title="Nouveau message" aria-label="Nouveau message">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
    </a>
</div>
<div class="wa-search">
    <label class="wa-search__box" for="waSearch">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
        <input type="search" id="waSearch" placeholder="Rechercher une discussion" autocomplete="off">
    </label>
</div>
<div class="msg-list" id="waMsgList">
    <?php if (!$conversations): ?>
        <div class="empty msg-empty-side">Aucune conversation pour le moment.</div>
    <?php else: ?>
        <?php foreach ($conversations as $c):
            $other = conversation_other_user($c, (int) $user['id']);
            if (!$other) {
                continue;
            }
            $unread = (int) ($c['unread'] ?? 0);
            $isActive = $activeConvId !== null && (int) $c['id'] === (int) $activeConvId;
            $searchHay = function_exists('mb_strtolower')
                ? mb_strtolower($other['prenom'] . ' ' . $other['nom'] . ' ' . ($c['last_body'] ?? ''), 'UTF-8')
                : strtolower($other['prenom'] . ' ' . $other['nom'] . ' ' . ($c['last_body'] ?? ''));
            ?>
            <a class="msg-thread<?= $unread ? ' has-unread' : '' ?><?= $isActive ? ' is-active' : '' ?>"
               href="<?= url('pages/messagerie/conversation.php?id=' . (int) $c['id']) ?>"
               data-search="<?= e($searchHay) ?>">
                <div class="msg-avatar" aria-hidden="true"><?= e(user_initials($other)) ?></div>
                <div class="msg-thread-body">
                    <div class="msg-thread-top">
                        <strong><?= e($other['prenom'] . ' ' . $other['nom']) ?></strong>
                        <span class="meta"><?= e($c['last_at'] ? format_datetime($c['last_at']) : '') ?></span>
                    </div>
                    <div class="msg-preview-row">
                        <span class="msg-preview"><?= e($c['last_body'] ? truncate_text($c['last_body'], 55) : 'Aucun message') ?></span>
                        <?php if ($unread > 0): ?>
                            <span class="wa-unread"><?= $unread ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<script>
(function () {
  var input = document.getElementById('waSearch');
  var list = document.getElementById('waMsgList');
  if (!input || !list) return;
  input.addEventListener('input', function () {
    var q = (input.value || '').toLowerCase().trim();
    list.querySelectorAll('.msg-thread').forEach(function (el) {
      var hay = el.getAttribute('data-search') || '';
      el.hidden = q !== '' && hay.indexOf(q) === -1;
    });
  });
})();
</script>
