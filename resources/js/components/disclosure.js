/**
 * Disclosure: one button showing or hiding one region. Powers the mobile
 * navigation drawer and any other "show more" toggle.
 *
 *   <button data-disclosure="panel-id" aria-expanded="false" aria-controls="panel-id">
 *   <div id="panel-id" hidden>
 */

export function init() {
    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-disclosure]');

        if (! trigger) {
            return;
        }

        const panel = document.getElementById(trigger.dataset.disclosure);

        if (! panel) {
            return;
        }

        const expanded = trigger.getAttribute('aria-expanded') === 'true';

        trigger.setAttribute('aria-expanded', String(! expanded));
        panel.hidden = expanded;
    });
}
