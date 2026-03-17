/**
 * ActionDispatcher — routes user actions to RC core.
 *
 * Public API:
 *   dispatch(action, value)  — execute a mass action (delete / archive / mark / flag / move)
 *   onPostAction(callback)   — register cleanup callback (runs after setTimeout(0))
 *
 * Owns:
 *   - All action button click listeners (archive, delete, move, mark-toggle,
 *     flag-toggle, massActionMenu, prevPage, nextPage)
 *   - display_message monkey-patch (suppress archivedreload toast)
 *   - responseaftermove handler (archive folder tree insertion)
 *   - schedulePostAction + closeMassActionPopovers
 */
(function() {
	'use strict';
	var ns = window.StratusSmartBar = window.StratusSmartBar || {};

	ns.ActionDispatcher = function(rcmail, bar) {
		this.rcmail = rcmail;
		this.bar    = bar;

		this._postActionCallbacks = [];
		this._postActionTimer     = null;
		this._archiveActionPending = false;

		// Resolve button DOM references
		this._archiveBtn     = document.getElementById('mp-action-archive') || bar.querySelector('.mp-action-btn.archive');
		this._markToggleBtn  = document.getElementById('mp-action-mark-toggle');
		this._flagToggleBtn  = document.getElementById('mp-action-flag-toggle');
		this._massActionMenu = document.getElementById('mp-massaction-menu');
		this._toggle         = document.getElementById('mp-mass-select-toggle');

		var actionBar    = bar.querySelector('.mp-mass-action-actions');
		this._deleteBtn  = actionBar ? actionBar.querySelector('.mp-action-btn.delete') : null;
		this._moveBtn    = actionBar ? actionBar.querySelector('.mp-action-btn.move')   : null;

		this._prevPageBtn = bar.querySelector('.prevpage');
		this._nextPageBtn = bar.querySelector('.nextpage');

		this._patchDisplayMessage();
		this._bindButtons();
		this._bindResponseAfterMove();
	};

	// ── Private helpers ────────────────────────────────────────────────

	ns.ActionDispatcher.prototype._logArchive = function(eventName, extra) {
		if (!window.console || typeof console.log !== 'function') return;
		console.log('[Stratus Archive]', eventName, extra || {});
	};

	/**
	 * Resolve the archive folder target from rcmail env.
	 */
	ns.ActionDispatcher.prototype._detectArchiveFolder = function() {
		if (this.rcmail.env.archive_folder) return this.rcmail.env.archive_folder;
		var mboxes = this.rcmail.env.mailboxes || {};
		for (var key in mboxes) {
			if (mboxes.hasOwnProperty(key) && mboxes[key]['class'] === 'archive') {
				return key;
			}
		}
		return null;
	};

	/**
	 * Close any open listselect or mass-action popovers.
	 */
	ns.ActionDispatcher.prototype._closeMassActionPopovers = function() {
		if (!window.rcmail || typeof rcmail.hide_menu !== 'function') return;
		var safeEvent = {
			stopPropagation: function() {},
			preventDefault:  function() {},
			target:          this._toggle || this.bar || document.body,
			type:            'click'
		};
		this.rcmail.hide_menu('listselect-menu',    safeEvent);
		this.rcmail.hide_menu('mp-massaction-menu', safeEvent);
	};

	/**
	 * Schedule post-action cleanup via setTimeout(0).
	 */
	ns.ActionDispatcher.prototype._schedulePostAction = function() {
		if (this._postActionTimer) return;
		var self = this;
		this._postActionTimer = window.setTimeout(function() {
			self._postActionTimer = null;
			self._closeMassActionPopovers();
			for (var i = 0; i < self._postActionCallbacks.length; i++) {
				self._postActionCallbacks[i]();
			}
		}, 0);
	};

	/**
	 * Suppress the archivedreload toast from the archive plugin.
	 */
	ns.ActionDispatcher.prototype._patchDisplayMessage = function() {
		var self = this;
		var orig = this.rcmail.display_message;
		this.rcmail.display_message = function(msg, type, timeout, key) {
			if (self._archiveActionPending && type === 'confirmation') {
				var reloadLabel = self.rcmail.get_label('archivedreload', 'archive');
				if (reloadLabel && reloadLabel !== 'archivedreload' && msg === reloadLabel) {
					var archivedLabel = self.rcmail.get_label('archived', 'archive');
					if (archivedLabel && archivedLabel !== 'archived') {
						msg = archivedLabel;
					}
					self._logArchive('suppressed-archivedreload', { original: reloadLabel, replacement: msg });
				}
			}
			return orig.call(this, msg, type, timeout, key);
		};
	};

	/**
	 * Wire up all action button click listeners.
	 */
	ns.ActionDispatcher.prototype._bindButtons = function() {
		var self = this;

		// ── Archive ────────────────────────────────────────────────
		if (this._archiveBtn) {
			this._archiveBtn.addEventListener('click', function(e) {
				e.preventDefault();
				e.stopPropagation();
				if (self._archiveBtn.getAttribute('data-mp-folder-disabled') === 'true') return;
				self._closeMassActionPopovers();

				self._logArchive('archive-btn-click', {
					mailbox:            self.rcmail.env.mailbox,
					archiveTarget:      self._detectArchiveFolder(),
					hasArchiveFunction: typeof rcmail_archive === 'function'
				});

				// Primary: archive plugin handles selection, subfolder routing, mark-as-read
				if (typeof rcmail_archive === 'function') {
					self._archiveActionPending = true;
					self._schedulePostAction();
					rcmail_archive();
					return;
				}

				// Fallback: plain move when archive.js is not loaded
				var target = self._detectArchiveFolder();
				if (!target) return;
				var delim = self.rcmail.env.delimiter || '/';
				if (self.rcmail.env.mailbox === target ||
					(self.rcmail.env.mailbox || '').indexOf(target + delim) === 0) {
					return;
				}
				self._archiveActionPending = true;
				self.rcmail.move_messages(target);
				self._schedulePostAction();
			});
		}

		// ── Delete ────────────────────────────────────────────────
		if (this._deleteBtn) {
			this._deleteBtn.addEventListener('click', function() {
				self._schedulePostAction();
			});
		}

		// ── Move ──────────────────────────────────────────────────
		if (this._moveBtn) {
			this._moveBtn.addEventListener('click', function() {
				self._schedulePostAction();
			});

			// Popper.js miscalculates the move button's position because it sits inside
			// a flex-reordered container that also has overflow:hidden (.footer from
			// Elastic's layout CSS).  Fix the popover coordinates after Bootstrap renders.
			$(this._moveBtn).on('shown.bs.popover', function() {
				self._fixMovePopoverPosition();
			});
		}

		// ── Mark toggle ────────────────────────────────────────────
		if (this._markToggleBtn) {
			this._markToggleBtn.addEventListener('click', function(e) {
				e.preventDefault();
				e.stopPropagation();
				if (self._markToggleBtn.classList.contains('disabled')) return;
				self._closeMassActionPopovers();
				var action = self._markToggleBtn.getAttribute('data-toggle-action') || 'read';
				self.rcmail.command('mark', action);
				self._schedulePostAction();
			});
		}

		// ── Flag toggle ────────────────────────────────────────────
		if (this._flagToggleBtn) {
			this._flagToggleBtn.addEventListener('click', function(e) {
				e.preventDefault();
				e.stopPropagation();
				if (self._flagToggleBtn.classList.contains('disabled')) return;
				self._closeMassActionPopovers();
				var action = self._flagToggleBtn.getAttribute('data-toggle-action') || 'flagged';
				self.rcmail.command('mark', action);
				self._schedulePostAction();
			});
		}

		// ── More menu — bubble (standard mode) ────────────────────
		if (this._massActionMenu) {
			this._massActionMenu.addEventListener('click', function(e) {
				var menuLink = e.target && e.target.closest ? e.target.closest('a') : null;
				if (!menuLink || !self._massActionMenu.contains(menuLink)) return;
				if (menuLink.classList.contains('disabled') || menuLink.classList.contains('copy')) return;
				self._schedulePostAction();
			});
		}
	};

	/**
	 * After archive move completes, ensure any newly-created archive folder
	 * appears in the sidebar, then refresh unread counts.
	 */
	ns.ActionDispatcher.prototype._bindResponseAfterMove = function() {
		var self = this;
		this.rcmail.addEventListener('responseaftermove', function() {
			if (!self._archiveActionPending) return;
			self._archiveActionPending = false;

			var archiveRoot = self.rcmail.env.archive_folder;
			if (archiveRoot && self.rcmail.treelist && !self.rcmail.get_folder_li(archiveRoot)) {
				var delim   = self.rcmail.env.delimiter || '/';
				var rawName = archiveRoot.split(delim).pop();

				var displayName = self.rcmail.get_label('archivefolder', 'archive');
				if (!displayName || displayName === 'archivefolder') displayName = rawName;

				var jsName  = archiveRoot.replace(/\\/g, '\\\\').replace(/'/g, "\\'");
				var href    = self.rcmail.url('list', { _mbox: archiveRoot });
				var linkHtml = '<a href="' + href + '"'
					+ ' onclick="return rcmail.command(\'list\',\'' + jsName + '\',this,event)"'
					+ ' rel="' + self.rcmail.quote_html(archiveRoot) + '">'
					+ self.rcmail.quote_html(displayName)
					+ '</a>';

				var parts    = archiveRoot.split(delim);
				var parentId = parts.length > 1 ? parts.slice(0, -1).join(delim) : null;

				self.rcmail.treelist.insert({
					id:      archiveRoot,
					html:    linkHtml,
					classes: ['mailbox', 'archive']
				}, parentId, false);

				self.rcmail.set_unread_count_display(archiveRoot, false);
				self._logArchive('archive-folder-inserted', { folder: archiveRoot, parent: parentId });
			}

			self.rcmail.refresh();
		});
	};

	/**
	 * Reposition the folder-selector popover so it opens directly below the
	 * move button.  Called from the shown.bs.popover handler because Popper.js
	 * computes wrong coordinates when the trigger is inside a flex-reordered
	 * container with overflow:hidden (Elastic's .footer layout rule).
	 */
	ns.ActionDispatcher.prototype._fixMovePopoverPosition = function() {
		if (!this._moveBtn) return;

		var popoverId = this._moveBtn.getAttribute('aria-describedby');
		if (!popoverId) return;
		var popover = document.getElementById(popoverId);
		if (!popover) return;

		var rect    = this._moveBtn.getBoundingClientRect();
		var scrollX = window.pageXOffset || document.documentElement.scrollLeft || 0;
		var scrollY = window.pageYOffset || document.documentElement.scrollTop  || 0;
		var winW    = window.innerWidth  || document.documentElement.clientWidth || 0;
		var pw      = popover.offsetWidth;

		// Place the popover below the button, horizontally centred on it
		var top  = rect.bottom + scrollY;
		var left = rect.left + scrollX + rect.width / 2 - pw / 2;

		// Keep the popover within the viewport
		left = Math.max(scrollX + 4, Math.min(left, scrollX + winW - pw - 4));

		popover.style.transform = 'none';
		popover.style.top       = top  + 'px';
		popover.style.left      = left + 'px';

		// Align the arrow with the button's horizontal centre
		var arrow = popover.querySelector('.arrow');
		if (arrow) {
			var btnCx      = rect.left + rect.width / 2;
			var popoverBCR = popover.getBoundingClientRect();
			var arrowLeft  = btnCx - popoverBCR.left - arrow.offsetWidth / 2;
			arrow.style.left = Math.max(0, arrowLeft) + 'px';
		}
	};

	// ── Public API ─────────────────────────────────────────────────────

	/**
	 * Route a mass action to RC core.
	 */
	ns.ActionDispatcher.prototype.dispatch = function(action, value) {
		this.rcmail.command(action, value);
		this._schedulePostAction();
	};

	/**
	 * Register a callback to run after post-action cleanup (setTimeout 0).
	 */
	ns.ActionDispatcher.prototype.onPostAction = function(callback) {
		this._postActionCallbacks.push(callback);
	};

})();
