/**
 * Modals, backed by the native <dialog> element.
 *
 * The browser gives us focus trapping, Esc-to-close, inert background content
 * and top-layer stacking, so this module is only wiring:
 *
 *   [data-modal-open="id"]   opens the dialog with that id
 *   [data-modal-close]       closes the dialog it sits inside
 *   <dialog id="id" data-modal>
 */

export function open(id) {
    const dialog = document.getElementById(id);

    if (dialog instanceof HTMLDialogElement) {
        dialog.showModal();
    }

    return dialog;
}

export function close(dialog, returnValue = '') {
    if (dialog instanceof HTMLDialogElement && dialog.open) {
        dialog.close(returnValue);
    }
}

export function init() {
    document.addEventListener('click', (event) => {
        const opener = event.target.closest('[data-modal-open]');

        if (opener) {
            event.preventDefault();
            open(opener.dataset.modalOpen);

            return;
        }

        const closer = event.target.closest('[data-modal-close]');

        if (closer) {
            event.preventDefault();
            close(closer.closest('dialog'), closer.dataset.modalClose || '');
        }
    });

    // Click on the backdrop closes, unless the dialog opts out. The backdrop is
    // the dialog element itself; anything inside it is a descendant.
    document.addEventListener('mousedown', (event) => {
        const dialog = event.target.closest('dialog[data-modal]');

        if (dialog && event.target === dialog && ! dialog.hasAttribute('data-modal-static')) {
            close(dialog, 'dismiss');
        }
    });
}
