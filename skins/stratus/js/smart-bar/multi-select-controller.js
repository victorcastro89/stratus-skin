/**
 * MultiSelectController — manage the multiselect mode.
 *
 * Delegates entirely to Elastic's native checkbox selection mechanism:
 *   - enable_checkbox_selection() (called by Elastic's ui.js on init) adds a
 *     per-row <td class="selection"><input type="checkbox"> to every row.
 *   - The .withselection CSS class on #messagelist shows/hides those cells.
 *   - Each checkbox already calls select_row(uid, CONTROL_KEY, true) natively,
 *     so no monkey-patch or click interceptor is needed.
 *
 * isActive() reads directly from the DOM (.withselection class) — no shadow
 * flag.  enter()/exit() are the only writers.  Elastic's own
 * UI.toggle_list_selection() is also compatible as it writes the same class.
 *
 * Public API:
 *   isActive()    → boolean
 *   enter()       — show checkboxes, set bar active style
 *   exit()        — hide checkboxes, reset bar style
 *   toggle()      — flip state
 */
(function() {
	'use strict';
	var ns = window.StratusSmartBar = window.StratusSmartBar || {};

	/**
	 * @param {object} list — Roundcube message list widget
	 * @param {object} bar  — .mp-smart-bar DOM element
	 */
	ns.MultiSelectController = function(list, bar) {
		this.list = list;
		this.bar  = bar;
	};

	// ── Public API ─────────────────────────────────────────────────────

	/**
	 * Source of truth: the .withselection class on #messagelist.
	 * No shadow flag — the DOM is the state.
	 */
	ns.MultiSelectController.prototype.isActive = function() {
		var table = document.getElementById('messagelist');
		return table ? table.classList.contains('withselection') : false;
	};

	ns.MultiSelectController.prototype.enter = function() {
		this.bar.classList.add('mp-multiselect-mode');
		var table = document.getElementById('messagelist');
		if (table) table.classList.add('withselection');
	};

	ns.MultiSelectController.prototype.exit = function() {
		// Reset RC's flag so stale multi-select state doesn't linger
		this.list.multi_selecting = false;
		this.bar.classList.remove('mp-multiselect-mode');
		var table = document.getElementById('messagelist');
		if (table) table.classList.remove('withselection');
	};

	ns.MultiSelectController.prototype.toggle = function() {
		if (this.isActive()) this.exit(); else this.enter();
	};

})();
