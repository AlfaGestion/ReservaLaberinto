(function () {
    const storageKey = 'reservas_theme';
    const root = document.documentElement;
    const toggleId = 'adminThemeToggle';
    const forceLightTheme = root.dataset.forceLightTheme === '1';

    function getStoredTheme() {
        try {
            return localStorage.getItem(storageKey) === 'dark' ? 'dark' : 'light';
        } catch (error) {
            return 'light';
        }
    }

    function applyTheme(theme, options = {}) {
        const persist = options.persist !== false;
        const normalizedTheme = theme === 'dark' ? 'dark' : 'light';
        root.classList.toggle('theme-dark', normalizedTheme === 'dark');
        root.classList.toggle('theme-light', normalizedTheme !== 'dark');

        if (persist) {
            try {
                localStorage.setItem(storageKey, normalizedTheme);
            } catch (error) {
                // Ignore storage errors.
            }
        }

        const button = document.getElementById(toggleId);
        if (!button) {
            return;
        }

        const icon = button.querySelector('[data-theme-icon]');
        const nextThemeIsDark = normalizedTheme !== 'dark';

        if (icon) {
            icon.classList.remove('fa-moon', 'fa-sun');
            icon.classList.add(nextThemeIsDark ? 'fa-moon' : 'fa-sun');
        }

        button.setAttribute('aria-label', nextThemeIsDark ? 'Activar tema oscuro' : 'Activar tema claro');
        button.title = nextThemeIsDark ? 'Activar tema oscuro' : 'Activar tema claro';
    }

    function initThemeToggle() {
        if (forceLightTheme) {
            applyTheme('light', { persist: false });
            return;
        }

        applyTheme(getStoredTheme());

        const button = document.getElementById(toggleId);
        if (!button) {
            return;
        }

        button.addEventListener('click', () => {
            const nextTheme = root.classList.contains('theme-dark') ? 'light' : 'dark';
            applyTheme(nextTheme);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initThemeToggle, { once: true });
    } else {
        initThemeToggle();
    }
})();
