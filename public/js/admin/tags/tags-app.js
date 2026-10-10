/**
 * Admin Bill Review Tags Manager App
 * Pure decoupled JavaScript - zero Blade directives.
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('adminTagsApp', () => ({
        showNewTagModal: false,
        newCode: '',
        newLabel: '',
        newShortLabel: '',
        newColor: 'blue'
    }));
});

window.adminTagsApp = function() {
    return {
        showNewTagModal: false,
        newCode: '',
        newLabel: '',
        newShortLabel: '',
        newColor: 'blue'
    };
};
