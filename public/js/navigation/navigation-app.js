/**
 * Navigation Bar Alpine.js Component Decoupling
 * Handles theme toggling and live notifications dropdown
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

window.navigationThemeToggle = navigationThemeToggle;
window.navigationNotifications = navigationNotifications;
