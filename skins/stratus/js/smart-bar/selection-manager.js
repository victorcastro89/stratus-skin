/**
 * SelectionManager — tracks selected items in the message list.
 *
 * Public API:
 *   getCount()           → number
 *   getState()           → { count, anyUnread, anyUnflagged }
 *   clearAll()           — deselect via data model
 *   forceDeselectAll()   — belt-and-suspenders DOM sweep + data model clear
 *   onChanged(callback)  — register callback for selection changes
 *   refresh()            — re-emit current state to all callbacks
 */
(function() {
	'use strict';
	var ns = window.StratusSmartBar = window.StratusSmartBar || {};

	/**
	 * @param {object} rcmail
	 * @param {object} list  — Roundcube message list widget
	 * @param {object} bar   — .mp-smart-bar DOM element
	 */
	ns.SelectionManager = function(rcmail, list, bar) {
		this.rcmail     = rcmail;
		this.list       = list;
		this.bar        = bar;
		this._callbacks = [];

		var self = this;

		// List widget fires 'select' on every selection change
		if (list) {
			list.addEventListener('select', function() {
				self._notify();
			});
		}
	};

	// ── Private helpers ────────────────────────────────────────────────

	ns.SelectionManager.prototype._getSelectedRows = function() {
		var list     = this.list;
		var selected = list && list.get_selection ? list.get_selection() : [];
		var rows     = [];
		for (var i = 0; i < selected.length; i++) {
			var id  = selected[i];
			var row = null;
			if (typeof id === 'string') {
				row = document.getElementById(id) || document.getElementById('rcmrow' + id);
			}
			if (!row && list.rows && list.rows[id] && list.rows[id].obj) {
				row = list.rows[id].obj;
			}
			if (row) rows.push(row);
		}
		return rows;
	};

	ns.SelectionManager.prototype._isVisibleRow = function(row) {
		if (!row) return false;
		if (row.getClientRects && row.getClientRects().length > 0) return true;
		return !!row.offsetParent;
	};

	ns.SelectionManager.prototype._notify = function() {
		var state = this.getState();
		for (var i = 0; i < this._callbacks.length; i++) {
			this._callbacks[i](state);
		}
	};

	// ── Public API ─────────────────────────────────────────────────────

	ns.SelectionManager.prototype.getState = function() {
		var rows         = this._getSelectedRows();
		var anyUnread    = false;
		var anyUnflagged = false;
		var visibleCount = 0;
		for (var i = 0; i < rows.length; i++) {
			if (!this._isVisibleRow(rows[i])) continue;
			visibleCount++;
			if (rows[i].classList.contains('unread'))   anyUnread    = true;
			if (!rows[i].classList.contains('flagged')) anyUnflagged = true;
		}
		return { count: visibleCount, anyUnread: anyUnread, anyUnflagged: anyUnflagged };
	};

	ns.SelectionManager.prototype.getCount = function() {
		return this.getState().count;
	};

	/**
	 * Clear selection via data model.
	 */
	ns.SelectionManager.prototype.clearAll = function() {
		if (this.list) {
			this.list.clear_selection(); // fires 'select' → _notify
		}
	};

	/**
	 * Belt-and-suspenders deselect: data model clear + DOM sweep.
	 * Use this after mass actions to catch visual state drift.
	 */
	ns.SelectionManager.prototype.forceDeselectAll = function() {
		if (this.list) {
			this.list.clear_selection();
			this.list.multi_selecting = false;
			this.list.shift_start     = null;
		}
		this.rcmail.select_all_mode = false;
		var tbl = this.list && this.list.list;
		if (tbl) {
			var rows = tbl.querySelectorAll('tr.selected, tr.focused');
			for (var i = 0; i < rows.length; i++) {
				rows[i].classList.remove('selected', 'focused');
				rows[i].removeAttribute('aria-selected');
			}
			var boxes = tbl.querySelectorAll('.selection input:checked');
			for (var j = 0; j < boxes.length; j++) {
				boxes[j].checked = false;
			}
		}
		// 'select' event from clear_selection() will call _notify
	};

	ns.SelectionManager.prototype.onChanged = function(callback) {
		this._callbacks.push(callback);
	};

	ns.SelectionManager.prototype.refresh = function() {
		this._notify();
	};

})();
