<?php

function parametres_sections(array $user): array
{
    return [
        'compte' => [
            'label' => 'Compte',
            'desc' => 'Identité et contact',
            'icon' => 'user',
        ],
        'infos' => [
            'label' => match ($user['role']) {
                'entreprise' => 'Entreprise',
                'client' => 'Coordonnées',
                'partenaire' => 'Organisation',
                'employe' => 'Mon entreprise',
                default => 'Informations',
            },
            'desc' => match ($user['role']) {
                'entreprise' => 'Fiche publique',
                'client' => 'Vos coordonnées',
                'partenaire' => 'Votre organisation',
                'employe' => 'Entreprise liée',
                default => 'Détails du profil',
            },
            'icon' => match ($user['role']) {
                'entreprise', 'partenaire' => 'building',
                'client' => 'map',
                default => 'info',
            },
        ],
        'securite' => [
            'label' => 'Sécurité',
            'desc' => 'Mot de passe',
            'icon' => 'lock',
        ],
    ];
}

function parametres_base_path(array $user): string
{
    return match ($user['role']) {
        'entreprise' => 'pages/entreprise/profil.php',
        'employe' => 'pages/employe/profil.php',
        'client' => 'pages/client/profil.php',
        'partenaire' => 'pages/partenaire/profil.php',
        default => 'pages/entreprise/profil.php',
    };
}

function role_label_fr(string $role): string
{
    return match ($role) {
        'entreprise' => 'Entreprise',
        'employe' => 'Visiteur',
        'client' => 'Client',
        'partenaire' => 'Partenaire',
        default => $role,
    };
}

function settings_ico(string $name): string
{
    $icons = [
        'user' => '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="3.5"/><path d="M5 19a7 7 0 0 1 14 0"/></svg>',
        'building' => '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 21V5a2 2 0 0 1 2-2h8v18"/><path d="M14 9h4a2 2 0 0 1 2 2v10"/><path d="M8 7h2M8 11h2M8 15h2M16 13h2M16 17h2"/></svg>',
        'map' => '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 21s6-5.2 6-11a6 6 0 1 0-12 0c0 5.8 6 11 6 11z"/><circle cx="12" cy="10" r="2.2"/></svg>',
        'info' => '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 10v6M12 7h.01"/></svg>',
        'lock' => '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>',
    ];
    return $icons[$name] ?? $icons['info'];
}

function render_parametres(array $user, ?array $extra = null, ?array $entrepriseContext = null): void
{
    $sections = parametres_sections($user);
    $section = get('section', 'compte');
    if (!isset($sections[$section])) {
        $section = 'compte';
    }
    $base = parametres_base_path($user);
    $current = $sections[$section];
    $settingsLogo = ($user['role'] === 'entreprise' && !empty($extra['logo']))
        ? entreprise_logo_url($extra['logo'])
        : null;
    $orgName = match ($user['role']) {
        'entreprise' => $extra['raison_sociale'] ?? '',
        'client' => $extra['entreprise_nom'] ?? '',
        'partenaire' => $extra['organisation'] ?? '',
        'employe' => $entrepriseContext['raison_sociale'] ?? '',
        default => '',
    };
    ?>
    <div class="settings-page">
        <header class="settings-page__hero">
            <div>
                <p class="settings-page__eyebrow">Compte PLANIPRO</p>
                <h1 class="settings-page__title">Paramètres</h1>
                <p class="settings-page__lead">Gérez votre profil, vos informations et la sécurité de votre compte.</p>
            </div>
            <div class="settings-page__summary">
                <?php if ($settingsLogo): ?>
                    <img class="settings-avatar settings-avatar--img" src="<?= e($settingsLogo) ?>" alt="" width="56" height="56">
                <?php else: ?>
                    <div class="settings-avatar" aria-hidden="true"><?= e(user_initials($user)) ?></div>
                <?php endif; ?>
                <div>
                    <strong><?= e($user['prenom'] . ' ' . $user['nom']) ?></strong>
                    <span><?= e(role_label_fr($user['role'])) ?><?= $orgName !== '' ? ' · ' . e($orgName) : '' ?></span>
                </div>
            </div>
        </header>

        <div class="settings-app">
            <aside class="settings-nav" aria-label="Sections des paramètres">
                <p class="settings-nav__label">Menu</p>
                <nav class="settings-links">
                    <?php foreach ($sections as $key => $meta): ?>
                        <a class="settings-link<?= $section === $key ? ' is-active' : '' ?>"
                           href="<?= url($base . '?section=' . $key) ?>">
                            <span class="settings-link__ico" aria-hidden="true"><?= settings_ico($meta['icon']) ?></span>
                            <span class="settings-link__text">
                                <strong><?= e($meta['label']) ?></strong>
                                <small><?= e($meta['desc']) ?></small>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </nav>

                <div class="settings-nav__meta">
                    <div class="settings-meta-row">
                        <span>E-mail</span>
                        <strong title="<?= e($user['email']) ?>"><?= e($user['email']) ?></strong>
                    </div>
                    <div class="settings-meta-row">
                        <span>Rôle</span>
                        <strong><?= e(role_label_fr($user['role'])) ?></strong>
                    </div>
                    <a class="settings-nav__logout" href="<?= url('actions/logout.php') ?>">Se déconnecter</a>
                </div>
            </aside>

            <section class="settings-panel">
                <div class="settings-panel-head">
                    <div class="settings-panel-head__ico" aria-hidden="true"><?= settings_ico($current['icon']) ?></div>
                    <div>
                        <h2><?= e($current['label']) ?></h2>
                        <p>
                            <?php if ($section === 'compte'): ?>
                                Modifiez votre identité affichée dans PLANIPRO.
                            <?php elseif ($section === 'securite'): ?>
                                Protégez votre compte avec un mot de passe solide.
                            <?php elseif ($user['role'] === 'employe'): ?>
                                Informations de l’entreprise à laquelle votre compte est rattaché.
                            <?php elseif ($user['role'] === 'entreprise'): ?>
                                Ces informations apparaissent sur votre fiche publique PLANIPRO.
                            <?php elseif ($user['role'] === 'client'): ?>
                                Complétez vos coordonnées pour faciliter les échanges.
                            <?php else: ?>
                                Renseignez les informations de votre organisation.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <?php if ($section === 'compte'): ?>
                    <form method="post" action="<?= url('actions/profil_update.php') ?>" class="settings-form" autocomplete="off">
                        <?= csrf_field() ?>
                        <input type="hidden" name="section" value="compte">
                        <div class="settings-block">
                            <div class="settings-block__head">
                                <h3 class="settings-block__title">Identité</h3>
                                <p>Nom affiché auprès des autres utilisateurs.</p>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="prenom">Prénom <span class="req">*</span></label>
                                    <input type="text" id="prenom" name="prenom" value="<?= e($user['prenom']) ?>" required autocomplete="given-name">
                                </div>
                                <div class="form-group">
                                    <label for="nom">Nom <span class="req">*</span></label>
                                    <input type="text" id="nom" name="nom" value="<?= e($user['nom']) ?>" required autocomplete="family-name">
                                </div>
                            </div>
                        </div>
                        <div class="settings-block">
                            <div class="settings-block__head">
                                <h3 class="settings-block__title">Coordonnées de compte</h3>
                                <p>Votre e-mail sert d’identifiant de connexion.</p>
                            </div>
                            <div class="form-group">
                                <label for="email_readonly">Adresse e-mail</label>
                                <input type="email" id="email_readonly" value="<?= e($user['email']) ?>" disabled>
                                <p class="help-text">Non modifiable pour des raisons de sécurité.</p>
                            </div>
                            <div class="form-group">
                                <label for="telephone">Téléphone</label>
                                <input type="tel" id="telephone" name="telephone" value="<?= e($user['telephone'] ?? '') ?>"
                                       placeholder="Ex. +243 …" autocomplete="tel">
                            </div>
                        </div>
                        <div class="settings-form-footer">
                            <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                        </div>
                    </form>

                <?php elseif ($section === 'infos'): ?>
                    <?php if ($user['role'] === 'employe'): ?>
                        <div class="settings-readonly">
                            <?php if ($entrepriseContext): ?>
                                <div class="settings-info-grid">
                                    <div class="settings-info-item">
                                        <span class="settings-label">Raison sociale</span>
                                        <strong><?= e($entrepriseContext['raison_sociale'] ?? '—') ?></strong>
                                    </div>
                                    <div class="settings-info-item">
                                        <span class="settings-label">Secteur</span>
                                        <strong><?= e($entrepriseContext['secteur'] ?? '—') ?></strong>
                                    </div>
                                    <div class="settings-info-item">
                                        <span class="settings-label">Site web</span>
                                        <strong>
                                            <?php
                                            $siteWeb = sanitize_http_url($entrepriseContext['site_web'] ?? null);
                                            if ($siteWeb):
                                            ?>
                                                <a href="<?= e($siteWeb) ?>" target="_blank" rel="noopener noreferrer"><?= e($siteWeb) ?></a>
                                            <?php else: ?>
                                                —
                                            <?php endif; ?>
                                        </strong>
                                    </div>
                                    <div class="settings-info-item settings-span-2">
                                        <span class="settings-label">Adresse</span>
                                        <strong><?= e($entrepriseContext['adresse'] ?? '—') ?></strong>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="empty">Aucune entreprise n’est liée à votre compte.</div>
                            <?php endif; ?>
                        </div>

                    <?php elseif ($user['role'] === 'entreprise'): ?>
                        <form method="post" action="<?= url('actions/profil_update.php') ?>" class="settings-form" enctype="multipart/form-data" autocomplete="off">
                            <?= csrf_field() ?>
                            <input type="hidden" name="section" value="infos">
                            <div class="settings-block">
                                <div class="settings-block__head">
                                    <h3 class="settings-block__title">Profil public</h3>
                                    <p>Visible sur votre fiche entreprise.</p>
                                </div>
                                <div class="form-group">
                                    <label for="raison_sociale">Raison sociale <span class="req">*</span></label>
                                    <input type="text" id="raison_sociale" name="raison_sociale"
                                           value="<?= e($extra['raison_sociale'] ?? '') ?>" required
                                           placeholder="Nom de votre entreprise">
                                </div>
                                <div class="form-group logo-upload">
                                    <label for="logo">Logo</label>
                                    <div class="logo-upload__preview">
                                        <?= entreprise_avatar_html($extra ?? [], 'settings-logo-preview') ?>
                                        <div class="logo-upload__fields">
                                            <input type="file" id="logo" name="logo" accept="image/jpeg,image/png,image/webp,image/gif">
                                            <p class="help-text">
                                                <?= !empty($extra['logo'])
                                                    ? 'Choisissez une image pour remplacer le logo actuel (JPG, PNG, WEBP ou GIF — max. 2 Mo).'
                                                    : 'Formats acceptés : JPG, PNG, WEBP ou GIF — max. 2 Mo.' ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="settings-block">
                                <div class="settings-block__head">
                                    <h3 class="settings-block__title">Coordonnées</h3>
                                    <p>Adresse, secteur et présentation.</p>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="secteur">Secteur d’activité</label>
                                        <input type="text" id="secteur" name="secteur"
                                               value="<?= e($extra['secteur'] ?? '') ?>"
                                               placeholder="Ex. Services, Commerce…">
                                    </div>
                                    <div class="form-group">
                                        <label for="site_web">Site web</label>
                                        <input type="url" id="site_web" name="site_web"
                                               value="<?= e($extra['site_web'] ?? '') ?>"
                                               placeholder="https://www.exemple.com">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="adresse">Adresse</label>
                                    <textarea id="adresse" name="adresse" rows="3"
                                              placeholder="Adresse complète"><?= e($extra['adresse'] ?? '') ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="description">Présentation</label>
                                    <textarea id="description" name="description" rows="5"
                                              placeholder="Présentez brièvement votre entreprise, vos services…"><?= e($extra['description'] ?? '') ?></textarea>
                                </div>
                            </div>
                            <div class="settings-form-footer">
                                <button type="submit" class="btn btn-primary">Enregistrer l’entreprise</button>
                            </div>
                        </form>

                    <?php elseif ($user['role'] === 'client'): ?>
                        <form method="post" action="<?= url('actions/profil_update.php') ?>" class="settings-form" autocomplete="off">
                            <?= csrf_field() ?>
                            <input type="hidden" name="section" value="infos">
                            <div class="settings-block">
                                <div class="settings-block__head">
                                    <h3 class="settings-block__title">Coordonnées</h3>
                                    <p>Informations utilisées lors des échanges.</p>
                                </div>
                                <div class="form-group">
                                    <label for="entreprise_nom">Entreprise / organisation</label>
                                    <input type="text" id="entreprise_nom" name="entreprise_nom"
                                           value="<?= e($extra['entreprise_nom'] ?? '') ?>"
                                           placeholder="Nom de votre société">
                                </div>
                                <div class="form-group">
                                    <label for="adresse">Adresse</label>
                                    <textarea id="adresse" name="adresse" rows="3"
                                              placeholder="Adresse complète"><?= e($extra['adresse'] ?? '') ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="notes">Notes</label>
                                    <textarea id="notes" name="notes" rows="4"
                                              placeholder="Informations utiles (facultatif)"><?= e($extra['notes'] ?? '') ?></textarea>
                                </div>
                            </div>
                            <div class="settings-form-footer">
                                <button type="submit" class="btn btn-primary">Enregistrer les coordonnées</button>
                            </div>
                        </form>

                    <?php elseif ($user['role'] === 'partenaire'): ?>
                        <form method="post" action="<?= url('actions/profil_update.php') ?>" class="settings-form" autocomplete="off">
                            <?= csrf_field() ?>
                            <input type="hidden" name="section" value="infos">
                            <div class="settings-block">
                                <div class="settings-block__head">
                                    <h3 class="settings-block__title">Organisation</h3>
                                    <p>Détails de votre partenariat.</p>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="organisation">Organisation</label>
                                        <input type="text" id="organisation" name="organisation"
                                               value="<?= e($extra['organisation'] ?? '') ?>"
                                               placeholder="Nom de l’organisation">
                                    </div>
                                    <div class="form-group">
                                        <label for="type_partenariat">Type de partenariat</label>
                                        <input type="text" id="type_partenariat" name="type_partenariat"
                                               value="<?= e($extra['type_partenariat'] ?? '') ?>"
                                               placeholder="Ex. Commercial, Technique…">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="adresse">Adresse</label>
                                    <textarea id="adresse" name="adresse" rows="3"
                                              placeholder="Adresse complète"><?= e($extra['adresse'] ?? '') ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="notes">Notes</label>
                                    <textarea id="notes" name="notes" rows="4"
                                              placeholder="Informations utiles (facultatif)"><?= e($extra['notes'] ?? '') ?></textarea>
                                </div>
                            </div>
                            <div class="settings-form-footer">
                                <button type="submit" class="btn btn-primary">Enregistrer l’organisation</button>
                            </div>
                        </form>
                    <?php endif; ?>

                <?php else: ?>
                    <form method="post" action="<?= url('actions/profil_update.php') ?>" class="settings-form" autocomplete="off">
                        <?= csrf_field() ?>
                        <input type="hidden" name="section" value="securite">
                        <div class="settings-block settings-block--secure">
                            <div class="settings-block__head">
                                <h3 class="settings-block__title">Mot de passe</h3>
                                <p>Minimum 6 caractères. Utilisez une combinaison unique.</p>
                            </div>
                            <div class="form-group">
                                <label for="password_current">Mot de passe actuel <span class="req">*</span></label>
                                <input type="password" id="password_current" name="password_current" required autocomplete="current-password">
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="password">Nouveau mot de passe <span class="req">*</span></label>
                                    <input type="password" id="password" name="password" minlength="6" required autocomplete="new-password"
                                           placeholder="Minimum 6 caractères">
                                </div>
                                <div class="form-group">
                                    <label for="password_confirm">Confirmation <span class="req">*</span></label>
                                    <input type="password" id="password_confirm" name="password_confirm" minlength="6" required autocomplete="new-password"
                                           placeholder="Retapez le mot de passe">
                                </div>
                            </div>
                        </div>
                        <div class="settings-form-footer">
                            <button type="submit" class="btn btn-primary">Mettre à jour le mot de passe</button>
                        </div>
                    </form>
                <?php endif; ?>
            </section>
        </div>
    </div>
    <?php
}
