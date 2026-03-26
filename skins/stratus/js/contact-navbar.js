/**
 * Stratus Contact Navbar — manages selection state and mass actions
 * for the addressbook contact list.
 *
 * Much simpler than the mail smart bar: no conversation mode, no sort,
 * no archive/mark/flag. Just selection tracking + command dispatch.
 */
(function() {
	'use strict';
	if (!window.rcmail) return;

	rcmail.addEventListener('init', function() {
		var list = rcmail.contact_list;
		if (!list) return;

		var bar = document.getElementById('contact-list-navbar');
		if (!bar) return;

		var chip       = document.getElementById('mp-contact-selected-count');
		var checkbox   = document.getElementById('mp-contact-select-checkbox');
		var exportBtn  = document.getElementById('mp-contact-action-export');
		var assignBtn  = document.getElementById('mp-contact-action-group-assign');
		var removeBtn  = document.getElementById('mp-contact-action-group-remove');
		var deleteBtn  = bar.querySelector('.mp-action-btn.delete');
		var selectMenu = document.getElementById('listselect-menu');
		var table      = document.getElementById('contacts-table');

		var selectedCount = 0;

		// ── Selection tracking ──────────────────────────────────────
		function getSelectionCount() {
			var sel = list.get_selection ? list.get_selection() : [];
			return sel.length;
		}

		function isMultiSelectActive() {
			return table ? table.classList.contains('withselection') : false;
		}

		function enterMultiSelect() {
			bar.classList.add('mp-multiselect-mode');
			if (table) table.classList.add('withselection');
		}

		function exitMultiSelect() {
			list.multi_selecting = false;
			bar.classList.remove('mp-multiselect-mode');
			if (table) table.classList.remove('withselection');
		}

		function forceDeselectAll() {
			list.clear_selection();
			list.multi_selecting = false;
			list.shift_start = null;
			rcmail.select_all_mode = false;
			if (table) {
				var rows = table.querySelectorAll('tr.selected, tr.focused');
				for (var i = 0; i < rows.length; i++) {
					rows[i].classList.remove('selected', 'focused');
					rows[i].removeAttribute('aria-selected');
				}
				var boxes = table.querySelectorAll('.selection input:checked');
				for (var j = 0; j < boxes.length; j++) {
					boxes[j].checked = false;
				}
			}
		}

		function updateGroupButtons() {
			// group-assign and group-remove only work when viewing a group
			var inGroup = !!(rcmail.env.group && rcmail.env.group !== '');
			var hasSelection = selectedCount > 0;

			if (assignBtn) {
				if (hasSelection) {
					assignBtn.classList.remove('disabled');
					assignBtn.removeAttribute('aria-disabled');
				} else {
					assignBtn.classList.add('disabled');
					assignBtn.setAttribute('aria-disabled', 'true');
				}
			}
			if (removeBtn) {
				if (hasSelection && inGroup) {
					removeBtn.classList.remove('disabled');
					removeBtn.removeAttribute('aria-disabled');
				} else {
					removeBtn.classList.add('disabled');
					removeBtn.setAttribute('aria-disabled', 'true');
				}
			}
		}

		function onSelectionChange() {
			selectedCount = getSelectionCount();
			var showActive = isMultiSelectActive() || selectedCount > 0;

			if (showActive) {
				bar.classList.add('mp-has-selection');
				if (chip) {
					var label = rcmail.get_label('stratus_helper.selected_count') || '$count selected';
					chip.textContent = label.replace('$count', selectedCount);
				}
			} else {
				bar.classList.remove('mp-has-selection');
				if (chip) chip.textContent = '';
			}

			// Enable/disable export button
			if (exportBtn) {
				if (selectedCount > 0) {
					exportBtn.classList.remove('disabled');
					exportBtn.removeAttribute('aria-disabled');
				} else {
					exportBtn.classList.add('disabled');
					exportBtn.setAttribute('aria-disabled', 'true');
				}
			}

			updateGroupButtons();

			// Auto-exit multiselect when selection empties
			if (selectedCount === 0 && isMultiSelectActive()) {
				exitMultiSelect();
			}
		}

		list.addEventListener('select', onSelectionChange);

		// ── Post-action cleanup ─────────────────────────────────────
		function schedulePostAction() {
			window.setTimeout(function() {
				exitMultiSelect();
				forceDeselectAll();
				bar.classList.remove('mp-has-selection');
				if (chip) chip.textContent = '';
			}, 0);
		}

		// ── Action button wiring ────────────────────────────────────


		if (exportBtn) {
			exportBtn.addEventListener('click', function(e) {
				e.preventDefault();
				if (exportBtn.classList.contains('disabled')) return;
				rcmail.command('export-selected');
				// Don't clear selection after export — user may want to do more
			});
		}

		if (assignBtn) {
			assignBtn.addEventListener('click', function(e) {
				e.preventDefault();
				if (assignBtn.classList.contains('disabled')) return;
				rcmail.command('group-assign-selected', '', assignBtn, e);
			});
		}

		if (removeBtn) {
			removeBtn.addEventListener('click', function(e) {
				e.preventDefault();
				if (removeBtn.classList.contains('disabled')) return;
				rcmail.command('group-remove-selected');
				// Don't schedulePostAction() here — Roundcube's response handler
				// needs the selection intact to know which rows to remove.
				// The responseafter handler below cleans up instead.
			});
		}

		// ── Checkbox: enter/exit multiselect ────────────────────────
		if (checkbox) {
			checkbox.addEventListener('click', function(e) {
				e.preventDefault();
				if (selectedCount > 0) {
					forceDeselectAll();
					if (isMultiSelectActive()) exitMultiSelect();
					bar.classList.remove('mp-has-selection');
					if (chip) chip.textContent = '';
				} else if (isMultiSelectActive()) {
					exitMultiSelect();
				} else {
					enterMultiSelect();
				}
			});
			checkbox.addEventListener('keydown', function(e) {
				if (e.key === 'Enter' || e.key === ' ') {
					e.preventDefault();
					checkbox.click();
				}
			});
		}

		// ── Select-menu coordination ────────────────────────────────
		if (selectMenu) {
			selectMenu.addEventListener('click', function(e) {
				var item = e.target;
				while (item && item !== selectMenu && item.tagName.toLowerCase() !== 'a') {
					item = item.parentNode;
				}
				if (!item || item === selectMenu || item.tagName.toLowerCase() !== 'a') return;

				if (item.classList.contains('none')) {
					exitMultiSelect();
				} else if (item.classList.contains('selection')) {
					if (selectedCount > 0) {
						forceDeselectAll();
						exitMultiSelect();
						bar.classList.remove('mp-has-selection');
						if (chip) chip.textContent = '';
					} else {
						enterMultiSelect();
					}
				} else {
					enterMultiSelect();
				}
			}, true);
		}

		// ── RC lifecycle events ─────────────────────────────────────
		rcmail.addEventListener('listupdate', function() {
			onSelectionChange();
			updateGroupButtons();
		});

		rcmail.addEventListener('group-update', function() {
			updateGroupButtons();
		});

		// Initial state
		onSelectionChange();

		// ── Group badges on contact rows ────────────────────────────
		// When viewing the top-level address book (no group selected),
		// fetch group memberships and show badges next to contact names.
		function fetchGroupBadges() {
			var source = rcmail.env.source || '';
			// Only show badges at top level (no group filter active)
			if (rcmail.env.group) return;

			var table = document.getElementById('contacts-table');
			if (!table) return;

			var rows = table.querySelectorAll('tr[id^="rcmrow"]');
			if (!rows.length) return;

			var ids = [];
			for (var i = 0; i < rows.length; i++) {
				var cid = rows[i].id.replace('rcmrow', '');
				ids.push(cid);
			}

			rcmail.http_post('plugin.contact_groups_map', {
				_source: source,
				_ids: ids.join(',')
			});
		}

		rcmail.addEventListener('plugin.contact_groups_map_response', function(response) {
			var map = response.map || {};
			var table = document.getElementById('contacts-table');
			if (!table) return;

			// Update all visible rows — clear badges for contacts not in the map
			var rows = table.querySelectorAll('tr[id^="rcmrow"]');
			for (var r = 0; r < rows.length; r++) {
				var row = rows[r];
				var cid = row.id.replace('rcmrow', '');
				var nameCell = row.querySelector('td.name') || row.cells[0];
				if (!nameCell) continue;

				// Remove existing badges
				var existing = nameCell.querySelectorAll('.contact-group-badge');
				for (var i = 0; i < existing.length; i++) {
					existing[i].parentNode.removeChild(existing[i]);
				}

				// Add badges if contact has groups
				var groups = map[cid];
				if (groups) {
					for (var g = 0; g < groups.length; g++) {
						var badge = document.createElement('span');
						badge.className = 'contact-group-badge';
						badge.textContent = groups[g];
						nameCell.appendChild(badge);
					}
				}
			}
		});

		// Refresh badges after group membership changes
		rcmail.addEventListener('group-update', function() {
			fetchGroupBadges();
		});
		rcmail.addEventListener('responseaftergroup-addmembers', function() {
			fetchGroupBadges();
			rcmail.list_contacts();
			reloadContactFrame();
		});
		rcmail.addEventListener('responseafterdelete', function() {
			var listEmpty = rcmail.contact_list && rcmail.contact_list.rowcount === 0;
			if (listEmpty && rcmail.env.group) {
				rcmail.remove_group_item({ source: rcmail.env.source, id: rcmail.env.group });
			} else {
				rcmail.list_contacts();
			}
		});
		rcmail.addEventListener('responseaftergroup-delmembers', function() {
			// Deferred cleanup for group-remove (selection must survive until response)
			exitMultiSelect();
			bar.classList.remove('mp-has-selection');
			if (chip) chip.textContent = '';
			onSelectionChange();

			// If the contact list is now empty while viewing a group, CardDAV will
			// auto-delete the group in its shutdown (after the response). Remove it
			// from the sidebar immediately via remove_group_item — same path RC takes
			// after an explicit group-delete — so the UI stays consistent.
			var listEmpty = rcmail.contact_list && rcmail.contact_list.rowcount === 0;
			if (listEmpty && rcmail.env.group) {
				rcmail.remove_group_item({ source: rcmail.env.source, id: rcmail.env.group });
			} else {
				fetchGroupBadges();
				reloadContactFrame();
			}
		});

		// ── Cross-frame sync ───────────────────────────────────────
		// Reload the contact detail iframe after group changes from navbar
		function reloadContactFrame() {
			var frame = document.getElementById('contact-frame');
			if (!frame || !frame.contentWindow) return;
			try {
				var loc = frame.contentWindow.location.href;
				if (loc && loc !== 'about:blank' && loc.indexOf('watermark') === -1) {
					frame.contentWindow.location.reload();
				}
			} catch(e) {}
		}

		// Watch the iframe: when group toggles change inside it, refresh badges
		function watchContactFrame() {
			var frame = document.getElementById('contact-frame');
			if (!frame) return;

			frame.addEventListener('load', function() {
				try {
					var iwin = frame.contentWindow;
					if (!iwin || !iwin.rcmail) return;

					iwin.rcmail.addEventListener('responseaftergroup-addmembers', function() {
						fetchGroupBadges();
					});
					iwin.rcmail.addEventListener('responseaftergroup-delmembers', function() {
						fetchGroupBadges();
					});
					iwin.rcmail.addEventListener('responseaftersave', function() {
						rcmail.list_contacts();
					});
				} catch(e) {}
			});
		}
		watchContactFrame();

		// Fetch badges on initial load and on list updates
		rcmail.addEventListener('listupdate', function() {
			fetchGroupBadges();
		});
		fetchGroupBadges();
	});
})();
