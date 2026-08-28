/**
 * NBPDCL Meter Billing — Universal Keyboard Shortcuts Engine
 * Supports Single Keys (c, r, Enter, Space) and Multi-Key Combinations (Ctrl+C, Shift+M, Alt+1, Ctrl+Shift+Enter).
 */
(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        define([], factory);
    } else if (typeof module === 'object' && module.exports) {
        module.exports = factory();
    } else {
        root.KeyboardShortcuts = factory();
    }
}(typeof self !== 'undefined' ? self : this, function () {
    'use strict';

    const MODIFIER_KEYS = ['Control', 'Shift', 'Alt', 'Meta'];

    const SPECIAL_KEY_MAP = {
        ' ': 'Space',
        'Escape': 'Escape',
        'Enter': 'Enter',
        'Tab': 'Tab',
        'Backspace': 'Backspace',
        'Delete': 'Delete',
        'ArrowUp': 'ArrowUp',
        'ArrowDown': 'ArrowDown',
        'ArrowLeft': 'ArrowLeft',
        'ArrowRight': 'ArrowRight',
        'Home': 'Home',
        'End': 'End',
        'PageUp': 'PageUp',
        'PageDown': 'PageDown'
    };

    const KeyboardShortcuts = {
        /**
         * Extract canonical combo object from a native KeyboardEvent.
         */
        getEventCombo: function (e) {
            const modifiers = [];
            if (e.ctrlKey) modifiers.push('Ctrl');
            if (e.altKey) modifiers.push('Alt');
            if (e.shiftKey) modifiers.push('Shift');
            if (e.metaKey) modifiers.push('Meta');

            const isModifierOnly = MODIFIER_KEYS.includes(e.key);
            let mainKey = e.key;

            if (SPECIAL_KEY_MAP[mainKey]) {
                mainKey = SPECIAL_KEY_MAP[mainKey];
            } else if (mainKey && mainKey.length === 1) {
                mainKey = mainKey.toUpperCase();
            }

            if (isModifierOnly) {
                return {
                    isModifierOnly: true,
                    modifiers: modifiers,
                    display: modifiers.length > 0 ? modifiers.join('+') + '+' : '',
                    combo: null,
                    rawKey: e.key
                };
            }

            const comboParts = [...modifiers, mainKey];
            const combo = comboParts.join('+');

            return {
                isModifierOnly: false,
                modifiers: modifiers,
                mainKey: mainKey,
                combo: combo,
                display: combo,
                rawKey: e.key
            };
        },

        /**
         * Normalize a shortcut string into standard canonical format for comparison.
         * Example: "ctrl + c" -> "ctrl+c", "ArrowDown" -> "arrowdown", "shift+m" -> "shift+m"
         */
        normalize: function (shortcut) {
            if (!shortcut) return '';
            const parts = String(shortcut).split('+').map(p => p.trim().toLowerCase());
            const modifiers = [];
            let key = '';

            for (const p of parts) {
                if (['ctrl', 'control'].includes(p)) modifiers.push('ctrl');
                else if (['alt'].includes(p)) modifiers.push('alt');
                else if (['shift'].includes(p)) modifiers.push('shift');
                else if (['meta', 'cmd', 'command'].includes(p)) modifiers.push('meta');
                else key = p;
            }

            // Normalise space
            if (key === 'space' || key === ' ') key = 'space';

            modifiers.sort();
            return [...modifiers, key].join('+');
        },

        /**
         * Check if a KeyboardEvent matches a configured target shortcut string.
         */
        matches: function (e, targetShortcut) {
            if (!targetShortcut) return false;

            const current = this.getEventCombo(e);
            if (current.isModifierOnly || !current.combo) return false;

            const targetNormalized = this.normalize(targetShortcut);
            const currentNormalized = this.normalize(current.combo);

            return targetNormalized === currentNormalized;
        },

        /**
         * Render shortcut as HTML KBD badges.
         */
        renderBadgesHtml: function (shortcut) {
            if (!shortcut) {
                return '<span class="text-slate-400 dark:text-slate-500 italic text-[11px]">Unset</span>';
            }
            const parts = String(shortcut).split('+').map(p => p.trim());
            return parts.map(p => {
                let display = p;
                if (p === 'ArrowDown') display = '↓ Down';
                else if (p === 'ArrowUp') display = '↑ Up';
                else if (p === 'ArrowLeft') display = '← Left';
                else if (p === 'ArrowRight') display = '→ Right';
                else if (p === 'Escape') display = 'Esc';
                return `<kbd class="inline-flex items-center justify-center px-2 py-1 text-[11px] font-mono font-black rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-100 border border-slate-300 dark:border-slate-700 shadow-xs">${display}</kbd>`;
            }).join(' <span class="text-slate-400 font-bold text-xs mx-0.5">+</span> ');
        },

        /**
         * Smart Rebinder Helper to attach to window during shortcut customization.
         */
        startRebindSession: function (options) {
            const onUpdate = options.onUpdate || function () {};
            const onComplete = options.onComplete || function () {};
            const onCancel = options.onCancel || function () {};

            let currentModifiers = [];

            const handleKeyDown = function (e) {
                e.preventDefault();
                e.stopPropagation();

                // Standalone Escape without modifiers cancels session
                if (e.key === 'Escape' && !e.ctrlKey && !e.altKey && !e.shiftKey && !e.metaKey) {
                    cleanup();
                    onCancel();
                    return;
                }

                const comboData = KeyboardShortcuts.getEventCombo(e);

                if (comboData.isModifierOnly) {
                    currentModifiers = comboData.modifiers;
                    onUpdate({
                        isWaitingKey: true,
                        display: comboData.display + ' [Press key...]',
                        modifiers: currentModifiers
                    });
                    return;
                }

                // Terminal key struck -> Complete rebinding
                cleanup();
                onComplete(comboData.combo, comboData);
            };

            const handleKeyUp = function (e) {
                if (MODIFIER_KEYS.includes(e.key)) {
                    // Update live modifier buffer
                    const remainingModifiers = [];
                    if (e.ctrlKey) remainingModifiers.push('Ctrl');
                    if (e.altKey) remainingModifiers.push('Alt');
                    if (e.shiftKey) remainingModifiers.push('Shift');
                    if (e.metaKey) remainingModifiers.push('Meta');

                    currentModifiers = remainingModifiers;
                    if (currentModifiers.length > 0) {
                        onUpdate({
                            isWaitingKey: true,
                            display: currentModifiers.join('+') + '+ [Press key...]',
                            modifiers: currentModifiers
                        });
                    } else {
                        onUpdate({
                            isWaitingKey: false,
                            display: 'Press any key or combo...',
                            modifiers: []
                        });
                    }
                }
            };

            const cleanup = function () {
                window.removeEventListener('keydown', handleKeyDown, { capture: true });
                window.removeEventListener('keyup', handleKeyUp, { capture: true });
            };

            window.addEventListener('keydown', handleKeyDown, { capture: true });
            window.addEventListener('keyup', handleKeyUp, { capture: true });

            return {
                cancel: function () {
                    cleanup();
                    onCancel();
                }
            };
        }
    };

    return KeyboardShortcuts;
}));
