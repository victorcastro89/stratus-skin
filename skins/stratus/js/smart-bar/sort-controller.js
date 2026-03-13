/**
 * SortController — manages the sort trigger label/arrow and sort popup.
 *
 * Public API:
 *   updateDisplay()        — refresh sort label and arrow from rcmail.env
 *   openPopup(event)       — open the lightweight sort popup
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
		this._sortMenu    = document.getElementById('mp-sort-menu');

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
		this._bindPopupHandlers();
		this._bindListUpdate();
	};

	// ── Private helpers ────────────────────────────────────────────────

	ns.SortController.prototype._bindSortTrigger = function() {
		var self = this;
		if (!this._sortTrigger) return;
		this._sortTrigger.addEventListener('click', function(e) {
			e.preventDefault();
			self.openPopup(e);
		});
	};

	ns.SortController.prototype._bindPopupHandlers = function() {
		var self = this;
		if (!this._sortMenu) return;
		this._sortMenu.addEventListener('click', function(e) {
			// Walk up to find the first element with a data-sort-* attribute
			var link = e.target;
			while (link && link !== self._sortMenu) {
				if (link.hasAttribute('data-sort-col')) {
					self._onSortColumnClick(e, link);
					return;
				}
				if (link.hasAttribute('data-sort-order')) {
					self._onSortDirectionClick(e, link);
					return;
				}
				link = link.parentNode;
			}
		});
	};

	ns.SortController.prototype._onSortColumnClick = function(e, link) {
		e.preventDefault();
		var col   = link.getAttribute('data-sort-col');
		var order = this.rcmail.env.sort_order || 'DESC';
		this.rcmail.set_list_options([], col, order);
		this.rcmail.hide_menu('mp-sort-menu');
	};

	ns.SortController.prototype._onSortDirectionClick = function(e, link) {
		e.preventDefault();
		var order = link.getAttribute('data-sort-order');
		var col   = this.rcmail.env.sort_col || 'date';
		this.rcmail.set_list_options([], col, order);
		this.rcmail.hide_menu('mp-sort-menu');
	};

	/**
	 * Sync popup state from rcmail.env. Called on every open so state is always fresh.
	 * Uses Elastic's native 'selected' class → a.selected::before shows fa-check
	 * automatically via .listing CSS (same pattern as #message-menu).
	 */
	ns.SortController.prototype._syncPopupState = function() {
		if (!this._sortMenu) return;

		var sortCol   = this.rcmail.env.sort_col   || 'date';
		var sortOrder = this.rcmail.env.sort_order  || 'DESC';

		// Current sort column gets 'selected' → Elastic shows checkmark via ::before
		var colLinks = this._sortMenu.querySelectorAll('[data-sort-col]');
		for (var i = 0; i < colLinks.length; i++) {
			colLinks[i].classList.toggle('selected', colLinks[i].getAttribute('data-sort-col') === sortCol);
		}

		// Current direction gets 'selected' → same checkmark pattern
		var dirOptions = this._sortMenu.querySelectorAll('[data-sort-order]');
		for (var j = 0; j < dirOptions.length; j++) {
			var checked = dirOptions[j].getAttribute('data-sort-order') === sortOrder;
			dirOptions[j].classList.toggle('selected', checked);
		}
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
		var labelKey    = this._sortColumnLabels[sortCol];
		var labelText   = labelKey ? this.rcmail.get_label(labelKey) : null;
		var sortByLabel = this.rcmail.get_label('listsorting') || 'Sort';
		if (!labelText || labelText === labelKey) labelText = sortByLabel;

		if (this._sortLabel) this._sortLabel.textContent = labelText;

		// Arrow direction: DESC = down (↓), ASC = up (↑)
		if (this._sortArrow) {
			this._sortArrow.classList.toggle('mp-sort-asc',  sortOrder === 'ASC');
			this._sortArrow.classList.toggle('mp-sort-desc', sortOrder !== 'ASC');
		}

		// Reset aria-expanded (popup closed after listupdate)
		this._sortTrigger.setAttribute('aria-expanded', 'false');
	};

	/**
	 * Open the sort popup anchored to the sort trigger.
	 */
	ns.SortController.prototype.openPopup = function(event) {
		this._syncPopupState();
		this.rcmail.command('menu-open', 'mp-sort-menu', event.target, event);
		this._sortTrigger.setAttribute('aria-expanded', 'true');
	};

})();
