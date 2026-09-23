/**
 * Dresses a revision note in the app's theme.
 *
 * The note runs sandboxed, in an origin of its own, so it cannot see the app's
 * stylesheet or storage. This sends it the tokens the app is painting with
 * right now; the block injected into the note (resources/views/notes/
 * theme.blade.php) maps its own palette onto them.
 *
 * Sent whenever either side could have missed it — the note announcing itself,
 * the frame loading, this module starting — and again on every theme or accent
 * change. Sending twice is harmless.
 */

import { resolved } from './theme';

const TOKENS = [
    'canvas', 'surface', 'surface-muted', 'border', 'text', 'text-muted', 'text-subtle',
    'accent', 'accent-soft', 'accent-contrast', 'info', 'info-soft', 'warning', 'warning-soft',
    'danger', 'danger-soft', 'success', 'success-soft',
].map((name) => `--color-${name}`).concat(['--font-sans', '--font-display']);

function send(frame) {
    const style = getComputedStyle(document.documentElement);
    const tokens = Object.fromEntries(TOKENS.map((name) => [name, style.getPropertyValue(name).trim()]));

    // The sandboxed note's origin is opaque, so '*' is the only target that
    // reaches it. Nothing sent here is private.
    frame.contentWindow?.postMessage({ type: 'app-theme', theme: resolved(), tokens }, '*');
}

export function init() {
    const frame = document.querySelector('[data-note-frame]');

    if (! frame) {
        return;
    }

    window.addEventListener('message', (event) => {
        if (event.source === frame.contentWindow && event.data?.type === 'note:ready') {
            send(frame);
        }
    });

    frame.addEventListener('load', () => send(frame));
    document.addEventListener('theme:changed', () => send(frame));
    document.addEventListener('accent:changed', () => send(frame));

    send(frame);
}
