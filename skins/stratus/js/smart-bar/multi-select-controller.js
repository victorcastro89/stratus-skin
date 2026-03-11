/**
 * MultiSelectController — manage the multiselect mode (checkbox-toggle for multi-select).
 *
 * Public API:
 *   isActive()         → boolean
 *   enter()            — activate multiselect mode
 *   exit()             — deactivate multiselect mode
 *   toggle()           — flip state
 *   patchSelectRow()   — apply the select_row monkey-patch (call once after list is ready)
 */
(function() {
	'use strict';
	var ns = window.StratusSmartBar = window.StratusSmartBar || {};

	/**
	 * @param {object} list — Roundcube message list widget
	 * @param {object} bar  — .mp-smart-bar DOM element
	 */
	ns.MultiSelectController = function(list, bar) {
		this.list    = list;
		this.bar     = bar;
		this._active = false;
	};

	// ── Public API ─────────────────────────────────────────────────────

	ns.MultiSelectController.prototype.isActive = function() {
		return this._active;
	};

	ns.MultiSelectController.prototype.enter = function() {
		this._active = true;
		this.bar.classList.add('mp-multiselect-mode');
	};

	ns.MultiSelectController.prototype.exit = function() {
		this._active = false;
		this.bar.classList.remove('mp-multiselect-mode');
	};

	ns.MultiSelectController.prototype.toggle = function() {
		if (this._active) this.exit(); else this.enter();
	};

	/**
	 * Monkey-patch list.select_row so every row click in multiselect mode
	 * behaves like Ctrl+click (toggle individual rows) without the user
	 * needing to hold a modifier key.
	 *
	 * Signature matches Roundcube's own select_row(id, mod_key, with_mouse).
	 * mod_key = 1 is CONTROL_KEY in Roundcube's list widget.
	 */
	ns.MultiSelectController.prototype.patchSelectRow = function() {
		var self = this;
		var list = this.list;
		if (!list || list._stratusMultiSelectPatched) return;
		var orig = list.select_row;
		list.select_row = function(id, mod_key, with_mouse) {
			if (self._active && with_mouse && !mod_key) {
				mod_key = 1; // CONTROL_KEY — toggle individual row
			}
			return orig.call(this, id, mod_key, with_mouse);
		};
		list._stratusMultiSelectPatched = true;
	};

})();
