/**
 * Undo Send – Client-side JS
 *
 * Intercepts the send command, shows a #messagestack countdown toast
 * with an Undo link, and delays SMTP delivery. Purely client-side.
 *
 * Works with elastic, larry-based skins, and stratus.
 *
 * @version 0.1.0
 */
(function () {
    'use strict';

    if (!window.rcmail) return;

    var undoSend = {
        // ── State ──────────────────────────────────
        state: 'idle',      // idle | counting | sending
        timer: null,
        remaining: 0,
        delay: 0,
        sendArgs: null,
        _hadAutoSave: false,

        // ── Init ───────────────────────────────────

        init: function () {
            var delay = rcmail.env.undo_send_delay;
            if (!delay || delay <= 0) return;
            if (rcmail.env.action !== 'compose') return;

            this.delay = delay;
            this.hookSend();
            this.hookKeys();
        },

        /**
         * Wrap rcmail.command to intercept 'send'.
         * This catches button clicks, Ctrl+Enter, and any other trigger.
         */
        hookSend: function () {
            var orig = rcmail.command;
            var self = this;

            rcmail.command = function (command, props, obj, event) {
                if (command === 'send' && !self.bypassing && self.state === 'idle') {
                    return self.intercept(props, obj, event);
                }
                return orig.apply(rcmail, arguments);
            };
        },

        /**
         * ESC key cancels the countdown.
         */
        hookKeys: function () {
            var self = this;
            $(document).on('keyup.undosend', function (e) {
                if (e.which === 27 && self.state === 'counting') {
                    self.cancel();
                }
            });
        },

        // ── Intercept ──────────────────────────────

        /**
         * Called instead of the real send command.
         * Starts the countdown timer.
         */
        intercept: function (props, obj, event) {
            this.state = 'counting';
            this.remaining = this.delay;
            this.sendArgs = { props: props, obj: obj, event: event };

            this.lockForm();
            this.showToast();

            var self = this;
            this.timer = setInterval(function () {
                self.remaining--;
                if (self.remaining <= 0) {
                    self.doSend();
                } else {
                    self.updateToast();
                }
            }, 1000);

            return false; // prevent default send
        },

        // ── Send Now ───────────────────────────────

        /**
         * User clicked "Send now" — skip remaining countdown.
         */
        sendNow: function () {
            if (this.state !== 'counting') return;
            this.doSend();
        },

        // ── Cancel ─────────────────────────────────

        /**
         * User clicked Undo or pressed ESC.
         */
        cancel: function () {
            if (this.state !== 'counting') return;

            clearInterval(this.timer);
            this.timer = null;
            this.state = 'idle';

            this.unlockForm();
            this.hideToast();

            rcmail.display_message(
                rcmail.gettext('undo_send.sending_cancelled'),
                'notice',
                3000
            );
        },

        // ── Send ───────────────────────────────────

        /**
         * Countdown expired — fire the real send.
         */
        doSend: function () {
            clearInterval(this.timer);
            this.timer = null;
            this.state = 'sending';

            this.hideToast();

            // MUST unlock form before sending — disabled inputs won't be
            // included in the form submission.
            this.unlockForm();

            // Bypass our intercept and call the original send command
            this.bypassing = true;
            rcmail.command('send', this.sendArgs.props, this.sendArgs.obj, this.sendArgs.event);
            this.bypassing = false;

            // Reset state (may not execute if page redirects, which is fine)
            this.state = 'idle';
        },

        // ── Toast ──────────────────────────────────

        /**
         * Show the initial countdown toast in #messagestack.
         */
        showToast: function () {
            rcmail.display_message(this.buildMsg(), 'notice', 0, 'undo-send-countdown');

            var msgData = rcmail.messages && rcmail.messages['undo-send-countdown'];
            if (msgData && msgData.obj) {
                msgData.obj.addClass('mp-undo-toast');
            }
        },

        /**
         * Update the existing toast content each tick.
         * We access the DOM element directly since display_message's
         * same-key update path is reliable for .html() replacement.
         */
        updateToast: function () {
            var msgData = rcmail.messages && rcmail.messages['undo-send-countdown'];
            if (msgData && msgData.obj) {
                msgData.obj.html(this.buildMsg());
            }
        },

        /**
         * Remove the countdown toast.
         */
        hideToast: function () {
            var msgData = rcmail.messages && rcmail.messages['undo-send-countdown'];
            if (msgData && msgData.obj) {
                rcmail.hide_message(msgData.obj);
            }
        },

        /**
         * Build the toast HTML: "Sending in Xs… Send now · Undo"
         */
        buildMsg: function () {
            var text    = rcmail.gettext('undo_send.sending_in').replace('$s', this.remaining);
            var sendNow = rcmail.gettext('undo_send.send_now');
            var undo    = rcmail.gettext('undo_send.undo');
            return '<span class="mp-undo-text">' + text + '</span>'
                + '<a href="#" class="mp-undo-link mp-send-now-link" onclick="window.undoSend.sendNow(); return false;">'
                + sendNow + '</a>'
                + '<span class="mp-undo-sep" aria-hidden="true">·</span>'
                + '<a href="#" class="mp-undo-link mp-undo-cta" onclick="window.undoSend.cancel(); return false;">'
                + undo + '</a>';
        },

        // ── Form Lock/Unlock ───────────────────────

        /**
         * Lock compose form to prevent edits during countdown.
         */
        lockForm: function () {
            // Pause auto-save timer so a draft-save doesn't fire with
            // disabled inputs.
            if (rcmail.save_timer) {
                clearTimeout(rcmail.save_timer);
                this._hadAutoSave = true;
            }

            // Disable all visible form inputs
            $('#compose-content input, #compose-content select, #compose-content textarea')
                .not('[type=hidden]')
                .prop('disabled', true);

            // Disable send buttons — elastic uses <button>, larry uses <a>
            $('.button.send, .btn.send, #button-send')
                .addClass('disabled')
                .prop('disabled', true);

            // Lock TinyMCE rich text editor if active
            if (window.tinymce && tinymce.activeEditor) {
                try {
                    tinymce.activeEditor.getBody().setAttribute('contenteditable', 'false');
                } catch (e) { /* editor may not be ready */ }
            }
        },

        /**
         * Unlock compose form — re-enable all inputs.
         */
        unlockForm: function () {
            // Re-enable form inputs
            $('#compose-content input, #compose-content select, #compose-content textarea')
                .not('[type=hidden]')
                .prop('disabled', false);

            // Re-enable send buttons
            $('.button.send, .btn.send, #button-send')
                .removeClass('disabled')
                .prop('disabled', false);

            // Unlock TinyMCE
            if (window.tinymce && tinymce.activeEditor) {
                try {
                    tinymce.activeEditor.getBody().setAttribute('contenteditable', 'true');
                } catch (e) { /* editor may not be ready */ }
            }

            // Resume auto-save timer
            if (this._hadAutoSave && rcmail.auto_save_start) {
                rcmail.auto_save_start();
                this._hadAutoSave = false;
            }
        }
    };

    // Expose globally so the onclick handler in the toast can reach it
    window.undoSend = undoSend;

    rcmail.addEventListener('init', function () {
        undoSend.init();
    });

})();
