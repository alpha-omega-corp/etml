/**
 * Application entrypoint.
 *
 * Two kinds of behaviour live here.
 *
 * Most of it is plain DOM code with no runtime dependency, wired through event
 * delegation on `document`. That means markup rendered later — by a partial
 * reload, an async fetch, or a JS-built fragment — works without being
 * re-initialised. To add one: create `components/<name>.js` exporting
 * `init()`, then register it in the `behaviours` list below.
 *
 * The rest is Alpine, for the pieces whose state is easier to read in the
 * markup than in a listener — the programme carousel is the first. To add one:
 * export a data factory and register it with `Alpine.data()` below, BEFORE
 * `Alpine.start()`.
 */

import Alpine from 'alpinejs';

import * as accent from './components/accent';
import * as copy from './components/copy';
import * as deck from './components/deck';
import * as disclosure from './components/disclosure';
import * as dismiss from './components/dismiss';
import * as drawer from './components/drawer';
import * as dropdown from './components/dropdown';
import * as modal from './components/modal';
import * as programEntry from './components/program-entry';
import * as rename from './components/rename';
import * as reset from './components/reset';
import * as tabs from './components/tabs';
import * as theme from './components/theme';
import carousel from './components/carousel';
import * as toast from './components/toast';

const behaviours = [theme, accent, copy, disclosure, dismiss, drawer, dropdown, modal, programEntry, rename, reset, tabs, toast, deck];

function start() {
    behaviours.forEach((behaviour) => behaviour.init());

    Alpine.data('carousel', carousel);
    Alpine.start();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start, { once: true });
} else {
    start();
}

// Exposed so inline scripts and console experiments can reach the helpers.
window.Alpine = Alpine;
window.ui = { theme, accent, modal, dropdown, tabs, toast: toast.toast };
