/**
 * « Effacer mes listes » — drops every mark the user has made, in every
 * language. It lives in the header, so it fires from any page: a deck on
 * screen clears itself through the event, any other page reloads to show the
 * new figures.
 */
export function init() {
    document.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-reset]');

        if (! button) {
            return;
        }

        event.preventDefault();

        if (! confirm('Effacer vos listes ?')) {
            return;
        }

        try {
            const response = await fetch(button.dataset.reset, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
            });

            if (! response.ok) {
                throw new Error('HTTP ' + response.status);
            }

            if (document.getElementById('deck-data')) {
                document.dispatchEvent(new CustomEvent('cards:reset'));
            } else {
                window.location.reload();
            }
        } catch (error) {
            window.ui?.toast?.('Réinitialisation impossible.', { variant: 'danger' });
        }
    });
}
