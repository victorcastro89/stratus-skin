// stratus skin — calendar format overrides
// Patches FullCalendar view options to show cleaner date formats:
//   Title:  "2 Mar – 8 Mar 2026"  instead of  "02 – 08 Mar 2026"
//   Column: "Wed 3"               instead of  "Wed 03-04"

(function () {
  'use strict';

  if (typeof jQuery === 'undefined') return;

  var origFC = jQuery.fn.fullCalendar;
  if (!origFC) return;

  jQuery.fn.fullCalendar = function () {
    var args = Array.prototype.slice.call(arguments);

    // Only intercept the initialization call (first arg is an options object)
    if (args.length && args[0] && typeof args[0] === 'object' && !args[0].jquery) {
      var opts = args[0];

      // Ensure views object exists
      if (!opts.views) opts.views = {};

      // ── Week view: title "D MMM YYYY", column "ddd D" ──────────────────
      if (!opts.views.week) opts.views.week = {};
      opts.views.week.titleFormat = 'D MMM YYYY';   // "2 Mar 2026" — FC auto-ranges to "2 Mar – 8 Mar 2026"
      opts.views.week.columnFormat = 'ddd D';        // "Wed 3"

      // ── Day view: title "dddd D MMM YYYY", column "dddd D" ─────────────
      if (!opts.views.day) opts.views.day = {};
      opts.views.day.titleFormat = 'dddd D MMM YYYY'; // "Wednesday 4 Mar 2026"
      opts.views.day.columnFormat = 'dddd D';          // "Wednesday 4"

      // ── List/agenda view: use same title style ──────────────────────────
      if (!opts.views.list) opts.views.list = {};
      opts.views.list.titleFormat = 'D MMM YYYY';

      args[0] = opts;
    }

    return origFC.apply(this, args);
  };

  // Copy any static properties/methods from the original
  jQuery.extend(jQuery.fn.fullCalendar, origFC);

})();
