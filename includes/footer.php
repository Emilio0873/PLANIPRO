<?php if (current_user()): ?>
        </main>
    </div>
</div>
<?php else: ?>
    </main>
    <footer class="guest-footer">
        <p>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?> — Gestion des rendez-vous et activités</p>
    </footer>
</div>
<?php endif; ?>
<script src="<?= asset('js/app.js') ?>?v=5"></script>
<?php if (!empty($extraScripts)) echo $extraScripts; ?>
</body>
</html>
