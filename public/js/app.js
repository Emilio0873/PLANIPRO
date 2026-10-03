document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.getElementById('menuToggle');
  const closeBtn = document.getElementById('sidebarClose');
  const sidebar = document.getElementById('sidebar');
  const backdrop = document.getElementById('sidebarBackdrop');

  const isMobileNav = () => window.matchMedia('(max-width: 900px)').matches;

  const setSidebar = (open) => {
    if (!sidebar) return;
    sidebar.classList.toggle('open', open);
    backdrop?.classList.toggle('is-visible', open);
    document.body.classList.toggle('sidebar-open', open);
    if (toggle) {
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
  };

  if (toggle && sidebar) {
    toggle.addEventListener('click', (e) => {
      e.stopPropagation();
      setSidebar(!sidebar.classList.contains('open'));
    });
  }

  closeBtn?.addEventListener('click', () => setSidebar(false));
  backdrop?.addEventListener('click', () => setSidebar(false));

  sidebar?.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => {
      if (isMobileNav()) setSidebar(false);
    });
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') setSidebar(false);
  });

  window.addEventListener('resize', () => {
    if (!isMobileNav()) setSidebar(false);
  });

  // État initial sûr sur téléphone
  if (isMobileNav()) setSidebar(false);

  const lpBurger = document.getElementById('lpBurger');
  const lpNav = document.getElementById('lpNav');
  lpBurger?.addEventListener('click', () => lpNav?.classList.toggle('is-open'));
  lpNav?.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => lpNav.classList.remove('is-open'));
  });

  document.querySelectorAll('[data-confirm]').forEach((el) => {
    el.addEventListener('click', (e) => {
      if (!confirm(el.getAttribute('data-confirm') || 'Confirmer cette action ?')) {
        e.preventDefault();
      }
    });
  });

  document.querySelectorAll('.card, .stat-card, .kanban-card').forEach((el, i) => {
    el.style.animationDelay = `${Math.min(i * 0.05, 0.45)}s`;
  });

  document.querySelectorAll('input, select, textarea').forEach((field) => {
    field.addEventListener('focus', () => field.parentElement?.classList.add('is-focus'));
    field.addEventListener('blur', () => field.parentElement?.classList.remove('is-focus'));
  });

  // Icône œil pour afficher / masquer le mot de passe
  const eyeIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M12 5c-5.5 0-9.5 5.2-10.7 6.7a1 1 0 0 0 0 1.2C2.5 14.3 6.5 19 12 19s9.5-4.7 10.7-6.1a1 1 0 0 0 0-1.2C21.5 10.2 17.5 5 12 5zm0 12a5 5 0 1 1 0-10 5 5 0 0 1 0 10zm0-8a3 3 0 1 0 0 6 3 3 0 0 0 0-6z"/></svg>';
  const eyeOffIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M3.3 2.3a1 1 0 0 0-1.4 1.4l3 3C3.3 8.3 1.8 10.5 1.3 11.5a1 1 0 0 0 0 1.1C2.5 14.3 6.5 19 12 19c1.9 0 3.6-.5 5.1-1.3l3.6 3.6a1 1 0 0 0 1.4-1.4l-19-19zM12 17c-4.1 0-7.4-3.4-8.7-5 .7-1 2.1-2.7 4.1-3.9l1.6 1.6A4 4 0 0 0 12 16a4 4 0 0 0 2.3-.7l1.5 1.5A8.5 8.5 0 0 1 12 17zm10.7-4.4c-.5.7-1.4 1.8-2.7 2.9l-2.1-2.1c.3-.4.5-.9.5-1.4a4 4 0 0 0-4-4c-.5 0-1 .2-1.4.5L10.4 6A9.6 9.6 0 0 1 12 5c5.5 0 9.5 5.2 10.7 6.7a1 1 0 0 1 0 1z"/></svg>';

  document.querySelectorAll('input[type="password"]').forEach((input) => {
    if (input.closest('.password-field')) return;
    const wrap = document.createElement('div');
    wrap.className = 'password-field';
    input.parentNode.insertBefore(wrap, input);
    wrap.appendChild(input);
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'password-toggle';
    btn.setAttribute('aria-label', 'Afficher le mot de passe');
    btn.innerHTML = eyeIcon;
    btn.addEventListener('click', () => {
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.innerHTML = show ? eyeOffIcon : eyeIcon;
      btn.setAttribute('aria-label', show ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
    });
    wrap.appendChild(btn);
  });

  // Afficher / masquer les formulaires « Ajouter »
  const setPanel = (id, open) => {
    const panel = document.getElementById(id);
    if (!panel) return;
    panel.hidden = !open;
    if (open) {
      panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
      const first = panel.querySelector('input:not([type=hidden]), select, textarea');
      first?.focus();
    }
  };

  document.querySelectorAll('[data-reveal]').forEach((btn) => {
    btn.addEventListener('click', () => setPanel(btn.getAttribute('data-reveal'), true));
  });
  document.querySelectorAll('[data-conceal]').forEach((btn) => {
    btn.addEventListener('click', () => setPanel(btn.getAttribute('data-conceal'), false));
  });

  // Basculer champs « nouveau contact » dans le formulaire RDV
  const syncContactFields = (select) => {
    const box = document.getElementById('nouveauContactFields');
    if (!select || !box) return;
    const isNew = String(select.value) === '0';
    box.hidden = !isNew;
    ['contact_prenom', 'contact_nom', 'contact_email', 'contact_password'].forEach((id) => {
      const el = document.getElementById(id);
      if (!el) return;
      el.required = isNew;
    });
  };
  document.querySelectorAll('[data-contact-switch]').forEach((select) => {
    syncContactFields(select);
    select.addEventListener('change', () => syncContactFields(select));
  });

  // Ouvrir le panneau si ancre #nouvelle-activite / #nouveau-rdv etc.
  if (location.hash) {
    const id = location.hash.slice(1);
    if (document.getElementById(id)?.classList.contains('add-panel')) {
      setPanel(id, true);
    }
  }
});
