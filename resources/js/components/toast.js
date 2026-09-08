/**
 * Toasts.
 *
 * Server-rendered flash messages arrive as markup inside `.toast-region` and
 * are auto-dismissed by this module. Client code can also push one:
 *
 *   import { toast } from './components/toast';
 *   toast('Saved', { variant: 'success' });
 */

const ICONS = {
    success: 'M4 10.5l3.5 3.5L16 5.5',
    warning: 'M10 7v5m0 3h.01M8.6 3.2L2.3 14a1.6 1.6 0 001.4 2.4h12.6A1.6 1.6 0 0017.7 14L11.4 3.2a1.6 1.6 0 00-2.8 0z',
    danger: 'M10 6.5v5m0 3h.01M10 2.5a7.5 7.5 0 100 15 7.5 7.5 0 000-15z',
    info: 'M10 9v5m0-8h.01M10 2.5a7.5 7.5 0 100 15 7.5 7.5 0 000-15z',
};

const DEFAULT_DURATION = 5000;

function region() {
    let element = document.querySelector('.toast-region');

    if (! element) {
        element = document.createElement('div');
        element.className = 'toast-region';
        // `polite` so a toast never interrupts what the user is doing.
        element.setAttribute('aria-live', 'polite');
        element.setAttribute('aria-atomic', 'false');
        document.body.append(element);
    }

    return element;
}

export function dismiss(element) {
    element.classList.add('toast--leaving');

    const remove = () => element.remove();

    element.addEventListener('animationend', remove, { once: true });
    // Belt and braces: reduced-motion skips the animation, so no event fires.
    setTimeout(remove, 400);
}

function schedule(element) {
    const duration = Number(element.dataset.toastDuration ?? DEFAULT_DURATION);

    if (duration <= 0) {
        return;
    }

    let timer = setTimeout(() => dismiss(element), duration);

    // Hovering or focusing a toast pauses its timer, so a long message can
    // actually be read.
    const pause = () => clearTimeout(timer);
    const resume = () => {
        timer = setTimeout(() => dismiss(element), duration);
    };

    element.addEventListener('mouseenter', pause);
    element.addEventListener('focusin', pause);
    element.addEventListener('mouseleave', resume);
    element.addEventListener('focusout', resume);
}

export function toast(message, { title = '', variant = 'info', duration = DEFAULT_DURATION } = {}) {
    const element = document.createElement('div');

    element.className = `toast toast--${variant}`;
    element.dataset.toast = '';
    element.dataset.toastDuration = String(duration);
    element.innerHTML = `
        <svg class="toast__icon" viewBox="0 0 20 20" stroke="currentColor" stroke-width="1.6"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="${ICONS[variant] ?? ICONS.info}" />
        </svg>
        <div class="toast__content">
            ${title ? `<p class="toast__title"></p>` : ''}
            <p class="toast__message"></p>
        </div>
        <button type="button" class="toast__dismiss" data-toast-dismiss aria-label="Dismiss">
            <svg viewBox="0 0 20 20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
                <path d="M5 5l10 10M15 5L5 15" />
            </svg>
        </button>
    `;

    // Set text content rather than interpolating into the template, so a
    // message containing markup can never inject it.
    if (title) {
        element.querySelector('.toast__title').textContent = title;
    }

    element.querySelector('.toast__message').textContent = message;

    region().append(element);
    schedule(element);

    return element;
}

export function init() {
    document.querySelectorAll('[data-toast]').forEach(schedule);

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-toast-dismiss]');

        if (button) {
            dismiss(button.closest('[data-toast]'));
        }
    });
}
