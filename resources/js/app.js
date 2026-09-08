/**
 * Application entrypoint.
 *
 * Every behaviour here is plain DOM code with no runtime dependency, wired
 * through event delegation on `document`. That means markup rendered later —
 * by a partial reload, an async fetch, or a JS-built fragment — works without
 * being re-initialised.
 *
 * To add a behaviour: create `components/<name>.js` exporting `init()`, then
 * register it in the list below.
 */

import * as accent from './components/accent';
import * as disclosure from './components/disclosure';
import * as dismiss from './components/dismiss';
import * as dropdown from './components/dropdown';
import * as modal from './components/modal';
import * as tabs from './components/tabs';
import * as theme from './components/theme';
import * as toast from './components/toast';

const behaviours = [theme, accent, disclosure, dismiss, dropdown, modal, tabs, toast];

function start() {
    behaviours.forEach((behaviour) => behaviour.init());
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start, { once: true });
} else {
    start();
}

// Exposed so inline scripts and console experiments can reach the helpers.
window.ui = { theme, accent, modal, dropdown, tabs, toast: toast.toast };
