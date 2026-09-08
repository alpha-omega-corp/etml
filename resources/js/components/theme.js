/**
 * Light / dark theme switching.
 *
 * The *initial* theme is applied by an inline script in the document head
 * (see resources/views/partials/theme-script.blade.php) so the page never
 * flashes the wrong colours. This module only handles user toggling afterwards.
 */

const STORAGE_KEY = 'theme';
const ORDER = ['light', 'dark', 'system'];

function systemTheme() {
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

/** The stored preference, which may be 'system'. */
export function preference() {
    try {
        const stored = localStorage.getItem(STORAGE_KEY);

        return ORDER.includes(stored) ? stored : 'system';
    } catch {
        // Private browsing, or site data blocked. Fall back to the system.
        return 'system';
    }
}

/** The theme actually on screen: never 'system'. */
export function resolved() {
    const value = preference();

    return value === 'system' ? systemTheme() : value;
}

export function apply(value) {
    const root = document.documentElement;

    if (value === 'system') {
        root.removeAttribute('data-theme');
    } else {
        root.dataset.theme = value;
    }

    try {
        localStorage.setItem(STORAGE_KEY, value);
    } catch {
        // Nothing to do — the theme still applies for this page view.
    }

    document.querySelectorAll('[data-theme-toggle]').forEach(sync);

    document.dispatchEvent(
        new CustomEvent('theme:changed', { detail: { preference: value, resolved: resolved() } }),
    );
}

function sync(toggle) {
    const active = resolved();

    toggle.setAttribute('aria-pressed', String(active === 'dark'));
    toggle.querySelectorAll('[data-theme-icon]').forEach((icon) => {
        icon.hidden = icon.dataset.themeIcon !== active;
    });
}

export function init() {
    document.querySelectorAll('[data-theme-toggle]').forEach(sync);

    document.addEventListener('click', (event) => {
        const toggle = event.target.closest('[data-theme-toggle]');

        if (! toggle) {
            return;
        }

        // A plain toggle flips between light and dark; `data-theme-cycle`
        // steps through light → dark → system instead.
        if (toggle.hasAttribute('data-theme-cycle')) {
            apply(ORDER[(ORDER.indexOf(preference()) + 1) % ORDER.length]);
        } else {
            apply(resolved() === 'dark' ? 'light' : 'dark');
        }
    });

    // Follow the OS while the preference is 'system'.
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (preference() === 'system') {
            document.querySelectorAll('[data-theme-toggle]').forEach(sync);
        }
    });
}
