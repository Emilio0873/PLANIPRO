(function () {
  function esc(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function ensureMenu() {
    var menu = document.getElementById('calContext');
    if (menu) return menu;
    menu = document.createElement('div');
    menu.id = 'calContext';
    menu.className = 'cal-context';
    menu.hidden = true;
    menu.innerHTML =
      '<div class="cal-context__card">' +
      '<button type="button" class="cal-context__close" aria-label="Fermer">×</button>' +
      '<div class="cal-context__head"></div>' +
      '<div class="cal-context__body"></div>' +
      '<div class="cal-context__actions"></div>' +
      '</div>';
    document.body.appendChild(menu);
    menu.querySelector('.cal-context__close').addEventListener('click', hideMenu);
    menu.addEventListener('click', function (e) {
      if (e.target === menu) hideMenu();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') hideMenu();
    });
    return menu;
  }

  function hideMenu() {
    var menu = document.getElementById('calContext');
    if (menu) menu.hidden = true;
  }

  function showMenu(jsEvent, props) {
    var menu = ensureMenu();
    var head = menu.querySelector('.cal-context__head');
    var body = menu.querySelector('.cal-context__body');
    var actions = menu.querySelector('.cal-context__actions');

    if (props.kind === 'rdv') {
      head.innerHTML = '<span class="tag-soft">Rendez-vous</span><strong>' + esc(props.titre) + '</strong>';
      body.innerHTML =
        '<div class="cal-context__row"><span>Statut</span><strong>' + esc(props.statutLabel) + '</strong></div>' +
        '<div class="cal-context__row"><span>Début</span><strong>' + esc(props.debut) + '</strong></div>' +
        '<div class="cal-context__row"><span>Fin</span><strong>' + esc(props.fin) + '</strong></div>' +
        '<div class="cal-context__row"><span>Personne</span><strong>' + esc(props.personne) + '</strong></div>' +
        '<div class="cal-context__row"><span>Type</span><strong>' + esc(props.type) + '</strong></div>' +
        '<div class="cal-context__row"><span>Lieu</span><strong>' + esc(props.lieu) + '</strong></div>' +
        (props.motif ? '<div class="cal-context__row"><span>Motif</span><strong>' + esc(props.motif) + '</strong></div>' : '');
      actions.innerHTML = '<a class="btn btn-sm btn-primary" href="' + esc(props.urlListe) + '">Voir la liste RDV</a>';
    } else if (props.kind === 'activite') {
      head.innerHTML = '<span class="tag-soft">Activité</span><strong>' + esc(props.titre) + '</strong>';
      body.innerHTML =
        '<div class="cal-context__row"><span>Statut</span><strong>' + esc(props.statutLabel) + '</strong></div>' +
        '<div class="cal-context__row"><span>Priorité</span><strong>' + esc(props.priorite) + '</strong></div>' +
        '<div class="cal-context__row"><span>Progression</span><strong>' + esc(props.progression) + ' %</strong></div>' +
        '<div class="cal-context__row"><span>Échéance</span><strong>' + esc(props.echeance) + '</strong></div>' +
        '<div class="cal-context__row"><span>Responsable</span><strong>' + esc(props.assigne) + '</strong></div>' +
        (props.description ? '<div class="cal-context__row"><span>Détail</span><strong>' + esc(props.description) + '</strong></div>' : '');
      actions.innerHTML = '<a class="btn btn-sm btn-primary" href="' + esc(props.urlListe) + '">Voir les activités</a>';
    } else {
      head.innerHTML = '<strong>Événement</strong>';
      body.innerHTML = '<p class="help-text">Détail indisponible.</p>';
      actions.innerHTML = '';
    }

    menu.hidden = false;
    var card = menu.querySelector('.cal-context__card');
    var isNarrow = window.matchMedia('(max-width: 640px)').matches;

    if (isNarrow) {
      card.style.left = '12px';
      card.style.right = '12px';
      card.style.width = 'auto';
      card.style.top = 'auto';
      card.style.bottom = '16px';
      card.classList.add('cal-context__card--sheet');
      return;
    }

    card.classList.remove('cal-context__card--sheet');
    card.style.right = '';
    card.style.bottom = '';
    card.style.width = '';
    var x = (jsEvent && jsEvent.clientX) || 40;
    var y = (jsEvent && jsEvent.clientY) || 40;
    card.style.left = '12px';
    card.style.top = '12px';
    var rect = card.getBoundingClientRect();
    var left = Math.min(x + 8, window.innerWidth - rect.width - 12);
    var top = Math.min(y + 8, window.innerHeight - rect.height - 12);
    card.style.left = Math.max(12, left) + 'px';
    card.style.top = Math.max(12, top) + 'px';
  }

  function mq() {
    return {
      phone: window.matchMedia('(max-width: 640px)').matches,
      tablet: window.matchMedia('(max-width: 900px)').matches
    };
  }

  function layoutForSize() {
    var size = mq();
    if (size.phone) {
      return {
        view: 'listWeek',
        toolbar: { left: 'prev,next', center: 'title', right: 'today listWeek,dayGridMonth' }
      };
    }
    if (size.tablet) {
      return {
        view: 'dayGridMonth',
        toolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,listWeek' }
      };
    }
    return {
      view: 'dayGridMonth',
      toolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek' }
    };
  }

  function init(el, eventsUrl) {
    if (!el || typeof FullCalendar === 'undefined') return null;
    var layout = layoutForSize();
    var wasPhone = mq().phone;
    var calendar = new FullCalendar.Calendar(el, {
      locale: 'fr',
      initialView: layout.view,
      headerToolbar: layout.toolbar,
      height: 'auto',
      contentHeight: 'auto',
      expandRows: true,
      stickyHeaderDates: true,
      dayMaxEvents: mq().tablet ? 2 : 3,
      moreLinkClick: 'popover',
      nowIndicator: true,
      navLinks: true,
      editable: false,
      events: eventsUrl,
      eventDisplay: 'block',
      eventTimeFormat: { hour: '2-digit', minute: '2-digit', meridiem: false },
      eventClick: function (info) {
        if (window.PlaniproCalendar) window.PlaniproCalendar.onEventClick(info);
      },
      windowResize: function () {
        var next = layoutForSize();
        var phone = mq().phone;
        calendar.setOption('headerToolbar', next.toolbar);
        calendar.setOption('dayMaxEvents', mq().tablet ? 2 : 3);
        if (phone && !wasPhone) {
          calendar.changeView('listWeek');
        } else if (!phone && wasPhone) {
          calendar.changeView('dayGridMonth');
        } else if (phone && calendar.view.type !== 'listWeek' && calendar.view.type !== 'listDay') {
          calendar.changeView('listWeek');
        }
        wasPhone = phone;
      }
    });
    calendar.render();
    return calendar;
  }

  window.PlaniproCalendar = {
    show: showMenu,
    hide: hideMenu,
    init: init,
    onEventClick: function (info) {
      info.jsEvent.preventDefault();
      showMenu(info.jsEvent, info.event.extendedProps || {});
    }
  };
})();
