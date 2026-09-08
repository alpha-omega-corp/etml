/**
 * Tabs following the ARIA authoring practices: one tab in the tab order, arrow
 * keys move between the rest.
 *
 *   [data-tabs]
 *     [role="tablist"] > [role="tab" aria-controls aria-selected]
 *     [role="tabpanel" id]
 */

function tabsOf(root) {
    return [...root.querySelectorAll('[role="tab"]')];
}

export function select(root, tab) {
    tabsOf(root).forEach((candidate) => {
        const selected = candidate === tab;
        const panel = document.getElementById(candidate.getAttribute('aria-controls'));

        candidate.setAttribute('aria-selected', String(selected));
        candidate.tabIndex = selected ? 0 : -1;

        if (panel) {
            panel.hidden = ! selected;
        }
    });
}

export function init() {
    document.querySelectorAll('[data-tabs]').forEach((root) => {
        const tabs = tabsOf(root);
        const active = tabs.find((tab) => tab.getAttribute('aria-selected') === 'true') ?? tabs[0];

        if (active) {
            select(root, active);
        }
    });

    document.addEventListener('click', (event) => {
        const tab = event.target.closest('[role="tab"]');

        if (tab) {
            event.preventDefault();
            select(tab.closest('[data-tabs]'), tab);
        }
    });

    document.addEventListener('keydown', (event) => {
        const tab = event.target.closest('[role="tab"]');

        if (! tab) {
            return;
        }

        const root = tab.closest('[data-tabs]');
        const tabs = tabsOf(root);
        const step = { ArrowRight: 1, ArrowLeft: -1 }[event.key];

        let next = null;

        if (step) {
            next = tabs[(tabs.indexOf(tab) + step + tabs.length) % tabs.length];
        } else if (event.key === 'Home') {
            next = tabs[0];
        } else if (event.key === 'End') {
            next = tabs.at(-1);
        }

        if (next) {
            event.preventDefault();
            select(root, next);
            next.focus();
        }
    });
}
