/**
 * Navigation Bar Alpine.js Component Decoupling
 * Handles theme toggling, live notifications dropdown, accessible menu interactions, and mobile drawer
 */
function navigationThemeToggle() {
    return {
        darkMode: document.documentElement.classList.contains('dark'),
        toggle() {
            this.darkMode = !this.darkMode;
            if (this.darkMode) {
                document.documentElement.classList.add('dark');
                localStorage.setItem('color-theme', 'dark');
            } else {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('color-theme', 'light');
            }
        }
    };
}

function navigationNotifications(endpoint, csrfToken) {
    return {
        open: false,
        unreadCount: 0,
        notifications: [],
        async fetchNotifs() {
            if (!endpoint) return;
            try {
                const res = await fetch(endpoint);
                const data = await res.json();
                this.unreadCount = data.unread_count || 0;
                this.notifications = data.notifications || [];
            } catch (e) {}
        },
        async markRead(id) {
            try {
                await fetch('/notifications/' + id + '/read', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
                });
                this.fetchNotifs();
            } catch (e) {}
        }
    };
}

function navigationDropdown(initialOpen = false) {
    return {
        open: initialOpen,
        toggle() {
            this.open = !this.open;
        },
        close() {
            this.open = false;
        }
    };
}

function navigationMobileDrawer(defaultSection = 'operations') {
    return {
        open: false,
        activeSection: defaultSection,
        toggle() {
            this.open = !this.open;
        },
        close() {
            this.open = false;
        },
        toggleSection(section) {
            this.activeSection = this.activeSection === section ? '' : section;
        }
    };
}

window.navigationThemeToggle = navigationThemeToggle;
window.navigationNotifications = navigationNotifications;
window.navigationDropdown = navigationDropdown;
window.navigationMobileDrawer = navigationMobileDrawer;
