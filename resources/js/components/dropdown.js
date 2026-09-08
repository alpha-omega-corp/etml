/**
 * Dropdown menus.
 *
 * Markup contract (see <x-ui.dropdown>):
 *   [data-dropdown]          the positioning wrapper
 *     [data-dropdown-trigger]  a <button aria-expanded aria-controls>
 *     [data-dropdown-menu]     the menu, `hidden` while closed
 *
 * Uses one delegated listener rather than per-instance wiring, so menus added
 * to the DOM later (partial swaps, async loads) work without re-initialising.
 */

function menuOf(root) {
    return root.querySelector('[data-dropdown-menu]');
}

function open(root) {
    const menu = root && menuOf(root);

    if (! menu) {
        return;
    }

    closeAll(root);

    const trigger = root.querySelector('[data-dropdown-trigger]');

    menu.hidden = false;
    trigger?.setAttribute('aria-expanded', 'true');
    menu.querySelector('.dropdown__item')?.focus();
}

export function close(root, { focusTrigger = false } = {}) {
    const menu = menuOf(root);
    const trigger = root.querySelector('[data-dropdown-trigger]');

    if (! menu || menu.hidden) {
        return;
    }

    menu.hidden = true;
    trigger?.setAttribute('aria-expanded', 'false');

    if (focusTrigger) {
        trigger?.focus();
    }
}

export function closeAll(except = null) {
    document.querySelectorAll('[data-dropdown]').forEach((root) => {
        if (root !== except) {
            close(root);
        }
    });
}

/** Move focus between items with the arrow keys. */
function moveFocus(root, step) {
    const items = [...menuOf(root).querySelectorAll('.dropdown__item:not([disabled])')];

    if (items.length === 0) {
        return;
    }

    const index = items.indexOf(document.activeElement);
    const next = index === -1 ? 0 : (index + step + items.length) % items.length;

    items[next].focus();
}

export function init() {
    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-dropdown-trigger]');

        if (trigger) {
            event.preventDefault();

            const root = trigger.closest('[data-dropdown]');
            const menu = root && menuOf(root);

            if (menu) {
                menu.hidden ? open(root) : close(root, { focusTrigger: true });
            }

            return;
        }

        // A click on a menu item still navigates or submits; just tidy up.
        const item = event.target.closest('.dropdown__item');

        if (item) {
            close(item.closest('[data-dropdown]'));

            return;
        }

        closeAll();
    });

    document.addEventListener('keydown', (event) => {
        const root = event.target.closest('[data-dropdown]');

        if (event.key === 'Escape') {
            root ? close(root, { focusTrigger: true }) : closeAll();

            return;
        }

        if (! root || ! menuOf(root) || menuOf(root).hidden) {
            // ArrowDown on a closed trigger opens the menu.
            if (event.key === 'ArrowDown' && event.target.closest('[data-dropdown-trigger]')) {
                event.preventDefault();
                open(root);
            }

            return;
        }

        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            moveFocus(root, event.key === 'ArrowDown' ? 1 : -1);
        }
    });

    // Close when focus leaves the menu entirely (tabbing out).
    document.addEventListener('focusin', (event) => {
        const root = event.target.closest('[data-dropdown]');

        if (! root) {
            closeAll();
        }
    });
}
