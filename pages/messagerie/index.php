<?php
require_once __DIR__ . '/../../includes/init.php';
$user = require_login();

$conversations = user_conversations((int) $user['id']);
$contacts = messagerie_contacts($user);
$roleLabels = [
    'entreprise' => 'Entreprise',
    'employe' => 'Visiteur',
    'client' => 'Client',
    'partenaire' => 'Partenaire',
];
$showNouveau = get('nouveau') === '1' || !$conversations;
$activeConvId = null;
$preselectTo = (int) get('to', 0);
if ($preselectTo > 0) {
    $showNouveau = true;
}

$pageTitle = 'Messagerie';
$bodyExtra = 'is-messaging';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="msg-app wa-skin<?= $showNouveau ? ' has-compose-open' : '' ?>">
    <aside class="msg-sidebar">
        <?php require __DIR__ . '/../../includes/messagerie_list.php'; ?>
    </aside>

    <section class="msg-panel">
        <?php if ($showNouveau): ?>
            <div class="msg-panel-head">
                <?php if ($conversations): ?>
                    <a class="msg-back" href="<?= url('pages/messagerie/index.php') ?>" aria-label="Retour">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 6-6 6 6 6"/></svg>
                    </a>
                <?php endif; ?>
                <strong>Nouveau message</strong>
            </div>
            <div class="msg-panel-body msg-compose-body">
                <?php if (!$contacts): ?>
                    <div class="empty">Aucun contact disponible dans votre entreprise.</div>
                <?php else: ?>
                    <form method="post" action="<?= url('actions/message_start.php') ?>" class="form msg-compose-form">
                        <?= csrf_field() ?>
                        <div class="form-group">
                            <label for="to_user_id">Destinataire</label>
                            <select name="to_user_id" id="to_user_id" required>
                                <option value="">Choisir…</option>
                                <?php foreach ($contacts as $c): ?>
                                    <option value="<?= (int) $c['id'] ?>"<?= $preselectTo === (int) $c['id'] ? ' selected' : '' ?>>
                                        <?= e($c['prenom'] . ' ' . $c['nom']) ?> — <?= e($roleLabels[$c['role']] ?? $c['role']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="body">Message</label>
                            <textarea name="body" id="body" rows="5" required placeholder="Écrivez votre message…"></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Envoyer</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="msg-welcome wa-welcome">
                <div class="wa-welcome__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="52" height="52" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M4 6a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v9a3 3 0 0 1-3 3H9l-5 4V6z"/></svg>
                </div>
                <h2>Messagerie PLANIPRO</h2>
                <p>Sélectionnez une discussion à gauche pour lire vos messages, comme sur WhatsApp Web.</p>
                <a class="btn btn-primary" href="<?= url('pages/messagerie/index.php?nouveau=1') ?>">Nouveau message</a>
            </div>
        <?php endif; ?>
    </section>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
