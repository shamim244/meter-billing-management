/**
 * User Panel Keyboard Shortcuts Manager
 * Decoupled Alpine.js Component
 */

function userShortcutsApp(config) {
    const c = config || window.userShortcutsConfig || {};

    return {
        shortcuts: Object.assign({}, c.shortcuts || {}),
        labels: Object.assign({}, c.labels || {}),
        saveUrl: c.saveUrl || '/user/shortcuts',
        resetUrl: c.resetUrl || '/user/shortcuts/reset',
        csrfToken: c.csrfToken || '',
        rebindingAction: null,
        rebindDisplay: '',
        rebindSession: null,
        isSaving: false,
        saveMessage: null,
        saveStatus: 'success',

        get conflicts() {
            if (!window.KeyboardShortcuts) return [];
            const map = {};
            const conflictingActions = [];
            for (const [action, key] of Object.entries(this.shortcuts)) {
                if (!key) continue;
                const norm = window.KeyboardShortcuts.normalize(key);
                if (map[norm]) {
                    conflictingActions.push({ key: key, actions: [map[norm], action] });
                } else {
                    map[norm] = action;
                }
            }
            return conflictingActions;
        },

        isActionInConflict(actionKey) {
            const currentKey = this.shortcuts[actionKey];
            if (!currentKey || !window.KeyboardShortcuts) return false;
            const norm = window.KeyboardShortcuts.normalize(currentKey);
            let count = 0;
            for (const [act, k] of Object.entries(this.shortcuts)) {
                if (k && window.KeyboardShortcuts.normalize(k) === norm) {
                    count++;
                }
            }
            return count > 1;
        },

        renderBadge(shortcut) {
            if (window.KeyboardShortcuts) {
                return window.KeyboardShortcuts.renderBadgesHtml(shortcut);
            }
            return shortcut || 'Unset';
        },

        startRebind(actionKey) {
            if (this.rebindSession) {
                this.rebindSession.cancel();
            }

            this.rebindingAction = actionKey;
            this.rebindDisplay = 'Press any key or combo (e.g. Ctrl+C)...';

            if (window.KeyboardShortcuts) {
                this.rebindSession = window.KeyboardShortcuts.startRebindSession({
                    onUpdate: (data) => {
                        this.rebindDisplay = data.display;
                    },
                    onComplete: (combo) => {
                        this.shortcuts[actionKey] = combo;
                        this.rebindingAction = null;
                        this.rebindSession = null;
                    },
                    onCancel: () => {
                        this.rebindingAction = null;
                        this.rebindSession = null;
                    }
                });
            }
        },

        cancelRebind() {
            if (this.rebindSession) {
                this.rebindSession.cancel();
            }
            this.rebindingAction = null;
            this.rebindSession = null;
        },

        applyPreset(preset) {
            if (preset === 'single') {
                this.shortcuts = {
                    copy_ca: 'c',
                    focus_reading: 'r',
                    auto_fill_reading: 'a',
                    submit_ok: 'Enter',
                    mark_doubt: '2',
                    mark_critical: '3',
                    next_card: 'ArrowRight',
                    prev_card: 'ArrowLeft',
                    open_remark: 'm',
                    exit_box: 'Escape'
                };
            } else if (preset === 'combo') {
                this.shortcuts = {
                    copy_ca: 'Ctrl+C',
                    focus_reading: 'Alt+R',
                    auto_fill_reading: 'Alt+A',
                    submit_ok: 'Ctrl+Enter',
                    mark_doubt: 'Alt+2',
                    mark_critical: 'Alt+3',
                    next_card: 'Alt+ArrowRight',
                    prev_card: 'Alt+ArrowLeft',
                    open_remark: 'Shift+M',
                    exit_box: 'Escape'
                };
            }
        },

        saveShortcuts() {
            this.isSaving = true;
            this.saveMessage = null;

            fetch(this.saveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: JSON.stringify({ shortcuts: this.shortcuts })
            })
            .then(r => r.json())
            .then(data => {
                this.isSaving = false;
                if (data.success) {
                    if (data.shortcuts) this.shortcuts = data.shortcuts;
                    this.saveStatus = 'success';
                    this.saveMessage = '✅ Custom keyboard shortcuts saved successfully!';
                    setTimeout(() => this.saveMessage = null, 4500);
                } else {
                    this.saveStatus = 'error';
                    this.saveMessage = '❌ ' + (data.message || 'Validation error');
                }
            })
            .catch(err => {
                this.isSaving = false;
                this.saveStatus = 'error';
                this.saveMessage = '❌ Failed to save shortcuts: ' + err.message;
            });
        },

        resetToDefaults() {
            if (!confirm('Reset all keyboard shortcuts back to system defaults?')) return;
            this.isSaving = true;
            this.saveMessage = null;

            fetch(this.resetUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                }
            })
            .then(r => r.json())
            .then(data => {
                this.isSaving = false;
                if (data.success) {
                    if (data.shortcuts) this.shortcuts = data.shortcuts;
                    this.saveStatus = 'success';
                    this.saveMessage = '🔄 Restored to system default shortcuts!';
                    setTimeout(() => this.saveMessage = null, 4500);
                }
            })
            .catch(err => {
                this.isSaving = false;
                this.saveStatus = 'error';
                this.saveMessage = '❌ Failed to reset shortcuts.';
            });
        }
    };
}

if (typeof window !== 'undefined') {
    window.userShortcutsApp = userShortcutsApp;
}

if (typeof document !== 'undefined') {
    document.addEventListener('alpine:init', () => {
        if (typeof Alpine !== 'undefined' && typeof Alpine.data === 'function') {
            Alpine.data('userShortcutsApp', (config) => userShortcutsApp(config));
        }
    });
}
