// stratus skin — calendar overflow menu stabilizer
// Keeps "Create" visible in header while "More" opens only secondary actions.

(function () {
  function initCalendarOverflowMenu() {
    var body = document.body;
    if (!body || !body.classList.contains('task-calendar')) {
      return;
    }

    var header = document.querySelector('#layout-content > .header');
    if (!header) {
      return;
    }

    var menu = header.querySelector('#toolbar-menu');
    var button = header.querySelector('a.toolbar-menu-button');

    if (!menu || !button || button.dataset.mpCalendarOverflowReady === '1') {
      return;
    }

    var menuClass = menu.getAttribute('class') || 'menu toolbar popupmenu listing iconized';
    var overflowId = 'toolbar-menu-calendar-overflow';
    var overflow = document.getElementById(overflowId);

    if (!overflow) {
      overflow = document.createElement('ul');
      overflow.id = overflowId;
      overflow.className = menuClass + ' hidden';
      overflow.setAttribute('aria-hidden', 'true');
      overflow.style.display = 'none';
      overflow.dataset.popupParent = 'layout-content-header';
      header.appendChild(overflow);
    }

    var moved = false;
    Array.prototype.slice.call(menu.querySelectorAll(':scope > li')).forEach(function (li) {
      var action = li.querySelector('a.button.print, a.button.import, a.button.export');
      if (action) {
        overflow.appendChild(li);
        moved = true;
      }
    });

    // Remove inline spacer after moving secondary actions out
    Array.prototype.slice.call(menu.querySelectorAll(':scope > li.spacer')).forEach(function (li) {
      li.parentNode.removeChild(li);
    });

    button.setAttribute('data-popup', overflowId);
    button.setAttribute('aria-owns', overflowId);
    button.dataset.mpCalendarOverflowReady = '1';

    // Re-bind popover if it was already initialized with old popup id
    if (window.jQuery) {
      var $button = window.jQuery(button);
      if ($button.data('bs.popover')) {
        $button.popover('dispose');
      }
      if (window.UI && typeof window.UI.popup_init === 'function') {
        window.UI.popup_init(button);
      }
    }

    // If actions were not available yet, try again shortly.
    if (!moved) {
      setTimeout(initCalendarOverflowMenu, 150);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCalendarOverflowMenu);
  } else {
    initCalendarOverflowMenu();
  }

  // Calendar toolbar is injected dynamically, so retry for a short period.
  var retries = 0;
  var intervalId = window.setInterval(function () {
    initCalendarOverflowMenu();
    retries += 1;
    if (retries > 40 || document.querySelector('a.toolbar-menu-button[data-mp-calendar-overflow-ready="1"]')) {
      window.clearInterval(intervalId);
    }
  }, 150);
})();
