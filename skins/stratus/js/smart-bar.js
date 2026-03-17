/**
 * Stratus Smart Bar — orchestrator.
 *
 * Instantiates the five concern modules and wires them together via
 * event callbacks.  All business logic lives in the sub-modules under
 * skins/stratus/js/smart-bar/.
 *
 * Load order (enforced by stratus_helper.php):
 *   1. smart-bar/selection-manager.js
 *   2. smart-bar/multi-select-controller.js
 *   3. smart-bar/mass-action-bar.js
 *   4. smart-bar/action-dispatcher.js
 *   5. smart-bar/sort-controller.js
 *   6. smart-bar.js  ← this file (orchestrator, must load last)
 */
(function() {
	'use strict';
	if (!window.rcmail) return;

	rcmail.addEventListener('init', function() {
		var ns = window.StratusSmartBar;
		if (!ns || !ns.SelectionManager) {
			console.error('[Stratus] StratusSmartBar modules not loaded — check script include order');
			return;
		}

		var list = rcmail.message_list;
		if (!list) return;

		var bar = document.querySelector('.mp-smart-bar');
		if (!bar) return;

		// ── Instantiate modules ──────────────────────────────────────
		var dispatcher  = new ns.ActionDispatcher(rcmail, bar);
		var selection   = new ns.SelectionManager(rcmail, list, bar);
		var multiSelect = new ns.MultiSelectController(list, bar);
		var massAction  = new ns.MassActionBar(rcmail, bar);
		var sort        = new ns.SortController(rcmail, bar);

		// ── Wire: selection changes → update bar UI ──────────────────
		selection.onChanged(function(state) {
			massAction.updateState(state, multiSelect.isActive());

			// Auto-exit multiselect when selection empties
			if (state.count === 0) {
				if (multiSelect.isActive()) multiSelect.exit();
			}
		});

		// ── Wire: post-action cleanup ────────────────────────────────
		// Runs inside setTimeout(0) — see ActionDispatcher._schedulePostAction
		dispatcher.onPostAction(function() {
			multiSelect.exit();
			selection.forceDeselectAll();
			massAction.showDefaultState();
		});

		// ── Select-menu coordination (needs multiple modules) ────────
		var selectMenu = document.getElementById('listselect-menu');
		if (selectMenu) {
			selectMenu.addEventListener('click', function(e) {
				var item = e.target;
				while (item && item !== selectMenu && item.tagName.toLowerCase() !== 'a') {
					item = item.parentNode;
				}
				if (!item || item === selectMenu || item.tagName.toLowerCase() !== 'a') return;

				var isNone = item.classList.contains('none');
				var isSelection = item.classList.contains('selection');

				if (isNone) {
					multiSelect.exit();
				} else if (isSelection) {
					// Toggle: if any selected, deselect all; else select all
					if (selection.getCount() > 0) {
						selection.forceDeselectAll();
						multiSelect.exit();
						massAction.showDefaultState();
					} else {
						multiSelect.enter();
						// Let RC command run for select-all
					}
				} else {
					multiSelect.enter();
				}
				// Do not preventDefault — RC command (select-all / select-none) runs normally
			}, true);
		}

		// ── Checkbox click: enter/exit multiselect (no popup) ───────
		var checkbox = document.getElementById('mp-mass-select-checkbox');
		if (checkbox) {
			checkbox.addEventListener('click', function(e) {
				e.preventDefault();
				if (selection.getCount() > 0) {
					// Always clear selection first — regardless of multiselect mode.
					// This covers the case where the user single-clicked a row (no
					// multiselect entered) and then clicks the checkbox to dismiss.
					selection.forceDeselectAll();
					if (multiSelect.isActive()) multiSelect.exit();
					massAction.showDefaultState();
				} else if (multiSelect.isActive()) {
					multiSelect.exit();
				} else {
					multiSelect.enter();
				}
			});
			checkbox.addEventListener('keydown', function(e) {
				if (e.key === 'Enter' || e.key === ' ') {
					e.preventDefault();
					checkbox.click();
				}
			});
		}

		// ── RC lifecycle events ──────────────────────────────────────
		// Clear stale toasts when the user switches folders
		rcmail.addEventListener('selectfolder', function() {
			rcmail.clear_messages();
		});

		// Refresh state and folder buttons after list reload
		rcmail.addEventListener('listupdate', function() {
			selection.refresh();
			massAction.updateFolderButtons();
			Promise.resolve().then(function() { massAction.reapplyFolderDisabledClasses(); });
		});

		rcmail.addEventListener('afterexpunge', function() {
			selection.refresh();
		});

		rcmail.addEventListener('responseaftermark', function() {
			selection.refresh();
			Promise.resolve().then(function() { massAction.reapplyFolderDisabledClasses(); });
		});

		// ── Accessibility: unread filter button label ────────────────
		var unreadFilterBtn = document.querySelector('.searchbar .button.unread, #mailsearchform .button.unread');
		if (unreadFilterBtn && !unreadFilterBtn.getAttribute('title')) {
			var showUnreadLabel = rcmail.get_label('stratus_helper.showunread') || rcmail.get_label('showunread') || 'Show unread messages';
			unreadFilterBtn.setAttribute('title', showUnreadLabel);
			unreadFilterBtn.setAttribute('aria-label', showUnreadLabel);
		}

		// ── Initialize ───────────────────────────────────────────────
		sort.updateDisplay();
		massAction.updateFolderButtons();
		selection.refresh();
	});
})();
