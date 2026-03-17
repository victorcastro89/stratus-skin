/**
 * MassActionBar — manages the visual state of the smart bar.
 *
 * Handles: active/default state toggle, count chip, mark/flag button labels,
 * folder-context button disabling.
 *
 * Public API:
 *   updateState(selectionState, isMultiSelect)  — refresh UI based on selection
 *   updateMarkFlagToggles(selectionState)        — set mark/flag button labels
 *   showDefaultState()                           — show sort + refresh view
 *   showActiveState(count)                       — show action buttons + count chip
 *   updateFolderButtons()                        — disable/enable buttons per folder context
 *   reapplyFolderDisabledClasses()               — re-stamp mp-folder-disabled after RC class swap
 *   onAction(callback)                           — register action emitter callback (forward compat)
 */
(function() {
	'use strict';
	var ns = window.StratusSmartBar = window.StratusSmartBar || {};

	/**
	 * @param {object} rcmail
	 * @param {object} bar    — .mp-smart-bar DOM element
	 */
	ns.MassActionBar = function(rcmail, bar) {
		this.rcmail = rcmail;
		this.bar    = bar;

		// Resolve DOM references once
		this._chip            = document.getElementById('mp-selected-count');
		this._toggle          = document.getElementById('mp-mass-select-toggle');
		this._markToggleBtn   = document.getElementById('mp-action-mark-toggle');
		this._flagToggleBtn   = document.getElementById('mp-action-flag-toggle');
		this._archiveBtn      = document.getElementById('mp-action-archive') || bar.querySelector('.mp-action-btn.archive');
		this._moveBtn         = document.getElementById('mp-action-move');
		this._deleteBtn       = bar.querySelector('.mp-action-btn.delete');
		this._prevPageBtn     = bar.querySelector('.prevpage');
		this._nextPageBtn     = bar.querySelector('.nextpage');
		this._countDisplay    = bar.querySelector('.mp-mass-action-count');
		this._massActionMenu  = document.getElementById('mp-massaction-menu');

		this._actionCallbacks = [];
		this._lastState       = { count: 0, anyUnread: false, anyUnflagged: false };

		this._initMoreMenuHandler();
	};

	// ── Private helpers ────────────────────────────────────────────────

	ns.MassActionBar.prototype._initMoreMenuHandler = function() {
		// placeholder — reserved for future more-menu handling
	};

	/**
	 * Update a toggle button's class, label, aria, and disabled state.
	 */
	ns.MassActionBar.prototype._updateToggleButtonEl = function(btn, modeClass, titleText, actionValue, disabled) {
		if (!btn) return;
		btn.classList.remove('read', 'unread', 'flag', 'unflag');
		btn.classList.add(modeClass);
		btn.setAttribute('title', titleText || '');
		btn.setAttribute('aria-label', titleText || '');
		btn.setAttribute('data-toggle-action', actionValue || '');
		var inner = btn.querySelector('.inner');
		if (inner) inner.textContent = titleText || '';
		if (disabled) {
			btn.classList.add('disabled');
			btn.setAttribute('aria-disabled', 'true');
		} else {
			btn.classList.remove('disabled');
			btn.removeAttribute('aria-disabled');
		}
	};

	/**
	 * Resolve the archive folder target from env.
	 * Duplicated here (also in ActionDispatcher) because this is a pure
	 * env-read with no side-effects — coupling to ActionDispatcher would
	 * create a circular dependency via the orchestrator.
	 */
	ns.MassActionBar.prototype._detectArchiveFolder = function() {
		if (this.rcmail.env.archive_folder) return this.rcmail.env.archive_folder;
		var mboxes = this.rcmail.env.mailboxes || {};
		for (var key in mboxes) {
			if (mboxes.hasOwnProperty(key) && mboxes[key]['class'] === 'archive') {
				return key;
			}
		}
		return null;
	};

	// ── Public API ─────────────────────────────────────────────────────

	/**
	 * Main update entry point — called whenever selection changes.
	 */
	ns.MassActionBar.prototype.updateState = function(selectionState, isMultiSelect) {
		this._lastState = selectionState;
		var count      = selectionState.count || 0;
		var showActive = isMultiSelect || count > 0;

		if (showActive) {
			this.showActiveState(count);
		} else {
			this.showDefaultState();
		}

		// Toggle is always clickable
		if (this._toggle) this._toggle.classList.remove('disabled');

		this.updateMarkFlagToggles(selectionState);
	};

	ns.MassActionBar.prototype.showActiveState = function(count) {
		this.bar.classList.add('mp-has-selection');
		if (this._chip) {
			var label = this.rcmail.get_label('stratus_helper.selected_count') || '$count selected';
			this._chip.textContent = label.replace('$count', count);
		}
		if (this._moveBtn) {
			this._moveBtn.classList.remove('disabled');
			this._moveBtn.removeAttribute('aria-disabled');
		}
	};

	ns.MassActionBar.prototype.showDefaultState = function() {
		this.bar.classList.remove('mp-has-selection');
		if (this._chip) this._chip.textContent = '';
		if (this._moveBtn) {
			this._moveBtn.classList.add('disabled');
			this._moveBtn.setAttribute('aria-disabled', 'true');
		}
	};

	/**
	 * Set the mark-as-read/unread and flag/unflag toggle button labels
	 * based on the current selection state.
	 */
	ns.MassActionBar.prototype.updateMarkFlagToggles = function(selectionState) {
		var count   = selectionState.count || 0;
		var disable = count === 0;

		var markAction = selectionState.anyUnread ? 'read'     : 'unread';
		var markClass  = selectionState.anyUnread ? 'read'     : 'unread';
		var markLabel  = selectionState.anyUnread
			? (this.rcmail.get_label('markread')   || 'Mark as read')
			: (this.rcmail.get_label('markunread') || 'Mark as unread');
		this._updateToggleButtonEl(this._markToggleBtn, markClass, markLabel, markAction, disable);

		var flagAction = selectionState.anyUnflagged ? 'flagged'   : 'unflagged';
		var flagClass  = selectionState.anyUnflagged ? 'flag'      : 'unflag';
		var flagLabel  = selectionState.anyUnflagged
			? (this.rcmail.get_label('markflagged')   || 'Flag')
			: (this.rcmail.get_label('markunflagged') || 'Unflag');
		this._updateToggleButtonEl(this._flagToggleBtn, flagClass, flagLabel, flagAction, disable);
	};

	/**
	 * Update folder-context button disabled state based on current mailbox.
	 * Uses raw IMAP paths from server env — never localised display names.
	 */
	ns.MassActionBar.prototype.updateFolderButtons = function() {
		var rcmail         = this.rcmail;
		var currentMailbox = rcmail.env.mailbox;
		var delim          = rcmail.env.delimiter || '/';

		var trashFolder  = rcmail.env.trash_mailbox;
		var isInTrash    = !!(trashFolder && currentMailbox === trashFolder);

		var archiveFolder = this._detectArchiveFolder();
		var isInArchive   = !!(archiveFolder && (
			currentMailbox === archiveFolder ||
			(currentMailbox || '').indexOf(archiveFolder + delim) === 0
		));

		var deleteBtn = this._deleteBtn;
		if (deleteBtn) {
			if (isInTrash) {
				deleteBtn.setAttribute('data-mp-folder-disabled', 'true');
				deleteBtn.classList.add('mp-folder-disabled');
				deleteBtn.setAttribute('aria-disabled', 'true');
			} else {
				deleteBtn.removeAttribute('data-mp-folder-disabled');
				deleteBtn.classList.remove('mp-folder-disabled');
				deleteBtn.removeAttribute('aria-disabled');
			}
		}

		var archiveBtn = this._archiveBtn;
		if (archiveBtn) {
			var disableArchive = !archiveFolder || isInArchive;
			if (disableArchive) {
				archiveBtn.setAttribute('data-mp-folder-disabled', 'true');
				archiveBtn.classList.add('mp-folder-disabled');
				archiveBtn.setAttribute('aria-disabled', 'true');
			} else {
				archiveBtn.removeAttribute('data-mp-folder-disabled');
				archiveBtn.classList.remove('mp-folder-disabled');
				archiveBtn.removeAttribute('aria-disabled');
			}
		}
	};

	/**
	 * Re-apply mp-folder-disabled class from data attribute.
	 *
	 * Roundcube swaps button class names via class/classAct on actions like
	 * responseaftermark, which strips any extra classes we added. This method
	 * re-stamps the disabling after Roundcube's own class replacement.
	 */
	ns.MassActionBar.prototype.reapplyFolderDisabledClasses = function() {
		var btns = [this._deleteBtn, this._archiveBtn];
		for (var i = 0; i < btns.length; i++) {
			var btn = btns[i];
			if (btn && btn.getAttribute('data-mp-folder-disabled') === 'true') {
				btn.classList.add('mp-folder-disabled');
				btn.setAttribute('aria-disabled', 'true');
			}
		}
	};

	/** Forward-compat hook for future action routing through MassActionBar. */
	ns.MassActionBar.prototype.onAction = function(callback) {
		this._actionCallbacks.push(callback);
	};

	ns.MassActionBar.prototype._emitAction = function(action, value) {
		for (var i = 0; i < this._actionCallbacks.length; i++) {
			this._actionCallbacks[i](action, value);
		}
	};

})();
