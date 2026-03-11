/**
 * SortController — manages the sort trigger label/arrow and list-options dialog.
 *
 * Public API:
 *   updateDisplay()        — refresh sort label and arrow from rcmail.env
 *   openDialog(event)      — open the list-options dialog
 */
(function() {
	'use strict';
	var ns = window.StratusSmartBar = window.StratusSmartBar || {};

	ns.SortController = function(rcmail, bar) {
		this.rcmail = rcmail;
		this.bar    = bar;

		this._sortTrigger = document.getElementById('mp-sort-trigger');
		this._sortLabel   = this._sortTrigger ? this._sortTrigger.querySelector('.mp-sort-label') : null;
		this._sortArrow   = this._sortTrigger ? this._sortTrigger.querySelector('.mp-sort-arrow')  : null;

		// Map from Roundcube's sort_col values to i18n label names.
		// Labels are exported from pagenav.html via <roundcube:add_label>.
		this._sortColumnLabels = {
			date:    'sentdate',
			arrival: 'arrival',
			from:    'from',
			to:      'to',
			fromto:  'fromto',
			subject: 'subject',
			size:    'size',
			cc:      'cc'
		};

		this._bindSortTrigger();
		this._bindListUpdate();
	};

	// ── Private helpers ────────────────────────────────────────────────

	ns.SortController.prototype._bindSortTrigger = function() {
		var self = this;
		if (!this._sortTrigger) return;
		this._sortTrigger.addEventListener('click', function(e) {
			e.preventDefault();
			self.openDialog(e);
		});
	};

	ns.SortController.prototype._bindListUpdate = function() {
		var self = this;
		this.rcmail.addEventListener('listupdate', function() {
			self.updateDisplay();
		});
	};

	// ── Public API ─────────────────────────────────────────────────────

	/**
	 * Refresh the sort label and arrow direction from rcmail.env.sort_col / sort_order.
	 */
	ns.SortController.prototype.updateDisplay = function() {
		if (!this._sortTrigger) return;

		var sortCol   = this.rcmail.env.sort_col   || 'date';
		var sortOrder = this.rcmail.env.sort_order  || 'DESC';

		// Resolve display label
		var labelKey     = this._sortColumnLabels[sortCol];
		var labelText    = labelKey ? this.rcmail.get_label(labelKey) : null;
		var sortByLabel  = this.rcmail.get_label('listsorting') || 'Sort';
		if (!labelText || labelText === labelKey) labelText = sortByLabel;

		if (this._sortLabel) this._sortLabel.textContent = labelText;

		// Arrow direction: DESC = down (↓), ASC = up (↑)
		if (this._sortArrow) {
			this._sortArrow.classList.toggle('mp-sort-asc',  sortOrder === 'ASC');
			this._sortArrow.classList.toggle('mp-sort-desc', sortOrder !== 'ASC');
		}

		// Update aria-expanded state (dialog handles its own open state,
		// but we reset it here so it reflects closed state after listupdate)
		this._sortTrigger.setAttribute('aria-expanded', 'false');
	};

	/**
	 * Open the list-options dialog (messagelistmenu).
	 */
	ns.SortController.prototype.openDialog = function(event) {
		this.rcmail.command('menu-open', 'messagelistmenu', this._sortTrigger, event);
	};

})();
