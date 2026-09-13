/**
 * Copy to clipboard.
 *
 * `<button data-copy="elementId">` copies that element's text and says so on
 * the button for a moment.
 */
export function init() {
    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-copy]');

        if (! button) {
            return;
        }

        const source = document.getElementById(button.dataset.copy);

        if (! source) {
            return;
        }

        navigator.clipboard.writeText(source.textContent).then(() => {
            const label = button.querySelector('.btn__label') ?? button;
            const original = label.textContent;

            label.textContent = 'Copié';
            setTimeout(() => { label.textContent = original; }, 1600);
        }).catch(() => {});
    });
}
