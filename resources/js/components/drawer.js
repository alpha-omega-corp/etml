/**
 * The unit drawer on phones.
 *
 * `[data-drawer-toggle]` opens it, `[data-drawer-close]` and the backdrop
 * close it, and so does Escape, opening a link inside it, or growing past the
 * width where the rail is permanent. The open state is a class on <body>, so
 * the stylesheet owns everything visual.
 */
const OPEN = 'is-nav-open';

export function init() {
    const toggle = document.querySelector('[data-drawer-toggle]');
    const drawer = document.querySelector('[data-drawer]');

    if (! toggle || ! drawer) {
        return;
    }

    const roomy = window.matchMedia('(min-width: 64rem)');

    function set(open) {
        document.body.classList.toggle(OPEN, open);
        toggle.setAttribute('aria-expanded', String(open));

        if (open) {
            drawer.querySelector('a, button')?.focus();
        }
    }

    toggle.addEventListener('click', () => set(! document.body.classList.contains(OPEN)));

    document.querySelector('[data-drawer-close]')?.addEventListener('click', () => {
        set(false);
        toggle.focus();
    });

    document.querySelector('[data-drawer-backdrop]')?.addEventListener('click', () => set(false));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && document.body.classList.contains(OPEN)) {
            set(false);
            toggle.focus();
        }
    });

    // Opening a unit closes the drawer behind you.
    drawer.addEventListener('click', (event) => {
        if (event.target.closest('a[href]')) {
            set(false);
        }
    });

    // Crossing to the wide layout, where the rail is always there.
    roomy.addEventListener('change', () => set(false));

    // The list scrolls horizontally on phones: bring the open unit into view.
    const current = drawer.querySelector('.nav-link.is-current');
    const list = current?.closest('.nav-list');

    if (list && list.scrollWidth > list.clientWidth) {
        current.scrollIntoView({ inline: 'center', block: 'nearest' });
    }
}
