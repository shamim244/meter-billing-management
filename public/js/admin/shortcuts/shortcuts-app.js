/**
 * Admin System Keyboard Shortcuts Manager
 * Decoupled Alpine.js Component
 */

function adminShortcutsApp(config) {
    const c = config || window.adminShortcutsConfig || {};

    return {
        shortcuts: Object.assign({}, c.shortcuts || {}),
        labels: Object.assign({}, c.labels || {}),
        rebindingAction: null,
        rebindDisplay: '',
        rebindSession: null,

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
        }
    };
}

if (typeof window !== 'undefined') {
    window.adminShortcutsApp = adminShortcutsApp;
}

if (typeof document !== 'undefined') {
    document.addEventListener('alpine:init', () => {
        if (typeof Alpine !== 'undefined' && typeof Alpine.data === 'function') {
            Alpine.data('adminShortcutsApp', (config) => adminShortcutsApp(config));
        }
    });
}
