/**
 * Accent palette switching.
 *
 * The accent is the second theme axis: it is independent of light/dark, so a
 * choice here survives a mode toggle and vice versa. The *initial* accent is
 * applied by the inline script in the document head (see
 * resources/views/partials/theme-script.blade.php) so the page never flashes
 * the wrong colour. This module only handles user switching afterwards.
 *
 * The palettes themselves live in resources/scss/base/_root.scss. Adding one
 * means adding a map entry there and a name to ACCENTS below.
 */

const STORAGE_KEY = 'accent';
const DEFAULT = 'green';

export const ACCENTS = ['green', 'orange', 'blue', 'purple'];

/** The stored accent, falling back to the default when unset or unknown. */
export function preference() {
    try {
        const stored = localStorage.getItem(STORAGE_KEY);

        return ACCENTS.includes(stored) ? stored : DEFAULT;
    } catch {
        // Private browsing, or site data blocked.
        return DEFAULT;
    }
}

export function apply(value) {
    if (! ACCENTS.includes(value)) {
        return;
    }

    document.documentElement.dataset.accent = value;

    try {
        localStorage.setItem(STORAGE_KEY, value);
    } catch {
        // Nothing to do — the accent still applies for this page view.
    }

    document.querySelectorAll('[data-accent-picker]').forEach(sync);

    document.dispatchEvent(new CustomEvent('accent:changed', { detail: { accent: value } }));
}

function sync(picker) {
    const active = preference();

    picker.querySelectorAll('[data-accent-option]').forEach((option) => {
        const selected = option.dataset.accentOption === active;

        option.setAttribute('aria-checked', String(selected));
        option.tabIndex = selected ? 0 : -1;
    });
}

/** Move focus within the group and select, the way a native radio group does. */
function step(picker, from, delta) {
    const options = [...picker.querySelectorAll('[data-accent-option]')];
    const next = options[(options.indexOf(from) + delta + options.length) % options.length];

    apply(next.dataset.accentOption);
    next.focus();
}

export function init() {
    document.querySelectorAll('[data-accent-picker]').forEach(sync);

    document.addEventListener('click', (event) => {
        const option = event.target.closest('[data-accent-option]');

        if (option) {
            apply(option.dataset.accentOption);
        }
    });

    document.addEventListener('keydown', (event) => {
        const option = event.target.closest('[data-accent-option]');

        if (! option) {
            return;
        }

        const delta = { ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1 }[event.key];

        if (delta) {
            event.preventDefault();
            step(option.closest('[data-accent-picker]'), option, delta);
        }
    });
}
