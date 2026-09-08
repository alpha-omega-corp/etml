/**
 * Generic dismiss behaviour: `[data-dismiss]` removes its closest
 * `[data-dismissible]` ancestor. Used by alerts, banners and anything else that
 * only needs to disappear on click.
 */

export function init() {
    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-dismiss]');

        if (! button) {
            return;
        }

        const target = button.dataset.dismiss
            ? document.getElementById(button.dataset.dismiss)
            : button.closest('[data-dismissible]');

        target?.remove();
    });
}
