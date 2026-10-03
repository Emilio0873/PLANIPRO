<?php
require_once __DIR__ . '/includes/init.php';

if (is_logged_in()) {
    redirect(role_home(current_user()['role']));
}

$login = url('pages/auth/login.php');
$register = url('pages/auth/register.php');
$imgA = url('assets/a.jpg');
$imgB = url('assets/b.jpg');
$imgC = url('assets/c.jpg');
$qEntreprises = get('q', '');
$entreprises = list_entreprises_publiques($qEntreprises !== '' ? $qEntreprises : null);
$publicTokens = public_guest_tokens();
$publicNotifCount = unread_public_notifications_count($publicTokens);
$publicNotifUrl = url('pages/public/notifications.php');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="PLANIPRO — organisez vos rendez-vous et activités professionnelles.">
  <title>PLANIPRO — Accueil</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>?v=15">
</head>
<body class="lp">
  <header class="lp-top lp-top--over">
    <a class="lp-logo" href="<?= e(url('index.php')) ?>">
      <img src="<?= e(logo_url()) ?>" alt="PlaniPro" class="brand-logo brand-logo--nav">
    </a>
    <nav class="lp-nav" id="lpNav">
      <a href="#fonctionnalites">Fonctionnalités</a>
      <a href="#entreprises">Entreprises</a>
      <a href="#visuel">Aperçu</a>
      <a href="#pourquoi">Pourquoi nous</a>
      <?php if ($publicTokens): ?>
        <a class="lp-public-bell" href="<?= e($publicNotifUrl) ?>" title="Mes notifications" aria-label="Mes notifications">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5"/><path d="M9.5 17a2.5 2.5 0 0 0 5 0"/></svg>
          <?php if ($publicNotifCount > 0): ?><span class="lp-public-bell__dot"><?= (int) $publicNotifCount ?></span><?php endif; ?>
        </a>
      <?php endif; ?>
      <a class="btn btn-ghost-light btn-sm lp-nav__login" href="<?= e($login) ?>">Se connecter</a>
      <a class="btn btn-primary btn-sm" href="<?= e($register) ?>">Commencer</a>
    </nav>
    <button type="button" class="lp-burger" id="lpBurger" aria-label="Menu">Menu</button>
  </header>

  <main>
    <section class="lp-hero" aria-label="Présentation PLANIPRO">
      <div class="lp-hero__media" aria-hidden="true" id="heroSlider" data-interval="3000">
        <img class="lp-hero__img is-active" src="<?= e($imgA) ?>" alt="" width="1600" height="1000" fetchpriority="high">
        <img class="lp-hero__img" src="<?= e($imgB) ?>" alt="" width="1600" height="1000" loading="eager">
        <img class="lp-hero__img" src="<?= e($imgC) ?>" alt="" width="1600" height="1000" loading="eager">
        <div class="lp-hero__shade"></div>
      </div>
      <div class="lp-hero__dots" id="heroDots" aria-hidden="true">
        <button type="button" class="is-active" data-slide="0" aria-label="Photo 1"></button>
        <button type="button" data-slide="1" aria-label="Photo 2"></button>
        <button type="button" data-slide="2" aria-label="Photo 3"></button>
      </div>
      <div class="lp-hero__content">
        <img class="lp-hero__logo reveal" data-reveal src="<?= e(logo_url()) ?>" alt="PlaniPro" width="280" height="280">
        <h1 class="reveal" data-reveal>Le planning professionnel, enfin clair.</h1>
        <p class="lp-hero__lead reveal" data-reveal>Rendez-vous, activités et équipes — une seule plateforme pour votre entreprise.</p>
        <div class="lp-hero__actions reveal" data-reveal>
          <a class="btn btn-primary btn-lg" href="<?= e($register) ?>">Créer mon compte</a>
          <a class="btn btn-ghost-light btn-lg" href="<?= e($login) ?>">Se connecter</a>
        </div>
      </div>
      <a class="lp-hero__scroll" href="#visuel" aria-label="Découvrir">
        <span></span>
      </a>
    </section>

    <section class="lp-section lp-entreprises" id="entreprises">
      <div class="lp-section__head reveal" data-reveal>
        <h2>Entreprises sur PLANIPRO</h2>
        <p>Découvrez les entreprises inscrites et écrivez-leur directement — avec ou sans compte.</p>
      </div>

      <form class="lp-ent-search reveal" data-reveal method="get" action="<?= e(url('index.php')) ?>#entreprises">
        <input type="search" name="q" value="<?= e($qEntreprises) ?>" placeholder="Rechercher une entreprise, un secteur…">
        <button type="submit" class="btn btn-primary">Rechercher</button>
      </form>

      <?php if (!$entreprises): ?>
        <div class="empty reveal" data-reveal>Aucune entreprise trouvée pour le moment.</div>
      <?php else: ?>
        <div class="lp-ent-grid">
          <?php foreach ($entreprises as $ent): ?>
            <article class="lp-ent-card reveal" data-reveal>
              <div class="lp-ent-card__top">
                <?= entreprise_avatar_html($ent) ?>
                <div>
                  <h3><?= e($ent['raison_sociale']) ?></h3>
                  <span class="lp-ent-tag"><?= e($ent['secteur'] ?: 'Entreprise') ?></span>
                </div>
              </div>
              <?php if (!empty($ent['description'])): ?>
                <p><?= e(truncate_text($ent['description'], 140)) ?></p>
              <?php elseif (!empty($ent['adresse'])): ?>
                <p><?= e(truncate_text($ent['adresse'], 120)) ?></p>
              <?php else: ?>
                <p>Présente sur PLANIPRO — contactez-la pour échanger.</p>
              <?php endif; ?>
              <div class="lp-ent-card__actions">
                <a class="btn btn-primary btn-sm" href="<?= e(url('pages/public/contacter.php?entreprise_id=' . (int) $ent['id'])) ?>">
                  Écrire un message
                </a>
                <?php
                  $siteWeb = sanitize_http_url($ent['site_web'] ?? null);
                  if ($siteWeb):
                ?>
                  <a class="btn btn-secondary btn-sm" href="<?= e($siteWeb) ?>" target="_blank" rel="noopener noreferrer">Site web</a>
                <?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <section class="lp-showcase" id="visuel">
      <div class="lp-showcase__inner">
        <figure class="lp-shot lp-shot--sign reveal" data-reveal>
          <img src="<?= e($imgB) ?>" alt="Panneau Rendez-vous" width="800" height="800" loading="lazy">
        </figure>
        <div class="lp-showcase__copy reveal" data-reveal>
          <h2>Des rendez-vous sans friction</h2>
          <p>Vos clients et partenaires demandent un créneau. Vous validez, reportez ou refusez. Tout reste traçable dans le calendrier.</p>
          <a class="btn btn-primary" href="<?= e($register) ?>">Essayer PLANIPRO</a>
        </div>
      </div>
      <div class="lp-showcase__inner lp-showcase__inner--reverse">
        <figure class="lp-shot lp-shot--note reveal" data-reveal>
          <img src="<?= e($imgC) ?>" alt="Carnet avec la mention Rendez-vous" width="1000" height="700" loading="lazy">
        </figure>
        <div class="lp-showcase__copy reveal" data-reveal>
          <h2>Ce qui compte, mis en avant</h2>
          <p>Priorités, échéances et rappels : vos activités et engagements restent visibles pour toute l’équipe.</p>
          <a class="btn btn-secondary" href="#fonctionnalites">Voir les fonctionnalités</a>
        </div>
      </div>
    </section>

    <section class="lp-section" id="fonctionnalites">
      <div class="lp-section__head reveal" data-reveal>
        <h2>Fonctionnalités principales</h2>
        <p>Tout ce qu’il faut pour piloter le planning de votre entreprise.</p>
      </div>
      <div class="lp-features">
        <article class="lp-feature reveal" data-reveal>
          <div class="lp-feature__mark" aria-hidden="true"></div>
          <h3>Rendez-vous</h3>
          <p>Demandes, validations, reports et historique avec des statuts clairs.</p>
        </article>
        <article class="lp-feature reveal" data-reveal>
          <div class="lp-feature__mark lp-feature__mark--2" aria-hidden="true"></div>
          <h3>Activités</h3>
          <p>Priorités, échéances et suivi Kanban pour votre équipe.</p>
        </article>
        <article class="lp-feature reveal" data-reveal>
          <div class="lp-feature__mark lp-feature__mark--3" aria-hidden="true"></div>
          <h3>Contacts</h3>
          <p>Clients et partenaires avec comptes liés à votre entreprise.</p>
        </article>
        <article class="lp-feature reveal" data-reveal>
          <div class="lp-feature__mark lp-feature__mark--4" aria-hidden="true"></div>
          <h3>Calendrier</h3>
          <p>Vue mensuelle, hebdomadaire et journalière de vos engagements.</p>
        </article>
        <article class="lp-feature reveal" data-reveal>
          <div class="lp-feature__mark lp-feature__mark--5" aria-hidden="true"></div>
          <h3>Messagerie</h3>
          <p>Échanges internes entre entreprise, équipe et contacts.</p>
        </article>
        <article class="lp-feature reveal" data-reveal>
          <div class="lp-feature__mark lp-feature__mark--6" aria-hidden="true"></div>
          <h3>Rapports</h3>
          <p>Suivi des RDV et activités, export et impression.</p>
        </article>
      </div>
    </section>

    <section class="lp-why" id="pourquoi">
      <div class="lp-section">
        <div class="lp-section__head reveal" data-reveal>
          <h2>Pourquoi PLANIPRO</h2>
          <p>Conçue pour fluidifier le quotidien des entreprises.</p>
        </div>
        <ul class="lp-why-list">
          <li class="reveal" data-reveal>
            <div class="lp-why-dot" aria-hidden="true"></div>
            <h3>Simple</h3>
            <p>Interface claire, prise en main rapide.</p>
          </li>
          <li class="reveal" data-reveal>
            <div class="lp-why-dot" aria-hidden="true"></div>
            <h3>Sécurisé</h3>
            <p>Comptes protégés et accès par rôle.</p>
          </li>
          <li class="reveal" data-reveal>
            <div class="lp-why-dot" aria-hidden="true"></div>
            <h3>Responsive</h3>
            <p>Ordinateur, tablette et smartphone.</p>
          </li>
          <li class="reveal" data-reveal>
            <div class="lp-why-dot" aria-hidden="true"></div>
            <h3>Productif</h3>
            <p>Moins de conflits d’horaires, plus de suivi.</p>
          </li>
        </ul>
      </div>
    </section>

    <section class="lp-cta reveal" data-reveal>
      <div class="lp-cta__inner">
        <h2>Prêt à organiser votre planning ?</h2>
        <p>Inscrivez votre entreprise et invitez votre équipe en quelques minutes.</p>
        <div class="lp-hero__actions">
          <a class="btn btn-primary btn-lg" href="<?= e($register) ?>">Commencer maintenant</a>
          <a class="btn btn-secondary btn-lg" href="<?= e($login) ?>">J’ai déjà un compte</a>
        </div>
      </div>
    </section>
  </main>

  <footer class="lp-foot">
    <div class="lp-foot__grid">
      <div>
        <img src="<?= e(logo_url()) ?>" alt="PlaniPro" class="brand-logo brand-logo--foot">
        <p class="lp-foot__tag">Organisez vos rendez-vous et activités professionnelles.</p>
      </div>
      <div>
        <strong>Produit</strong>
        <a href="#fonctionnalites">Fonctionnalités</a>
        <a href="<?= e($register) ?>">Inscription entreprise</a>
        <a href="<?= e($login) ?>">Connexion</a>
      </div>
      <div>
        <strong>Support</strong>
        <a href="mailto:contact@planipro.local">contact@planipro.local</a>
      </div>
    </div>
    <div class="lp-foot__copy">&copy; <?= date('Y') ?> PLANIPRO. Tous droits réservés.</div>
  </footer>

  <script>
    document.getElementById('lpBurger')?.addEventListener('click', () => {
      document.getElementById('lpNav')?.classList.toggle('is-open');
    });

    const topbar = document.querySelector('.lp-top');
    const onScroll = () => {
      if (!topbar) return;
      topbar.classList.toggle('is-scrolled', window.scrollY > 40);
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    const reveals = document.querySelectorAll('[data-reveal]');
    document.querySelectorAll('.lp-hero [data-reveal]').forEach((el) => el.classList.add('is-in'));
    if ('IntersectionObserver' in window) {
      const io = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-in');
            io.unobserve(entry.target);
          }
        });
      }, { threshold: 0.16, rootMargin: '0px 0px -8% 0px' });
      reveals.forEach((el) => {
        if (!el.classList.contains('is-in')) io.observe(el);
      });
    } else {
      reveals.forEach((el) => el.classList.add('is-in'));
    }

    (function heroSlider() {
      const slider = document.getElementById('heroSlider');
      if (!slider) return;
      const slides = Array.from(slider.querySelectorAll('.lp-hero__img'));
      const dots = Array.from(document.querySelectorAll('#heroDots button'));
      if (slides.length < 2) return;

      let index = 0;
      let timer = null;
      const delay = Number(slider.dataset.interval) || 3000;
      const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

      const goTo = (next) => {
        slides[index].classList.remove('is-active');
        dots[index]?.classList.remove('is-active');
        index = (next + slides.length) % slides.length;
        slides[index].classList.add('is-active');
        dots[index]?.classList.add('is-active');
      };

      const play = () => {
        if (reduce) return;
        clearInterval(timer);
        timer = setInterval(() => goTo(index + 1), delay);
      };

      dots.forEach((dot) => {
        dot.addEventListener('click', () => {
          goTo(Number(dot.dataset.slide) || 0);
          play();
        });
      });

      play();
    })();
  </script>
</body>
</html>
