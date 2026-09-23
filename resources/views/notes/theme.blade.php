{{--
    Injected into a note's own <head>, after its styles, so the page wears the
    app's theme. The note is sandboxed and cannot read the app's stylesheet, so
    the frame around it (resources/js/components/note-frame.js) posts the app's
    resolved tokens in, and this block maps the note's palette onto them.

    Nothing changes until the tokens arrive: the mapping hangs off
    `data-app-theme`, which only the message sets, so a note opened on its own
    keeps its original colours.
--}}
<link rel="stylesheet" href="https://fonts.bunny.net/css?family=fredoka:500,600,700|instrument-sans:400,400i,500,600,700&display=swap">
<style>
:root[data-app-theme] {
    color-scheme: dark;
    --paper: var(--color-canvas);
    --card: var(--color-surface);
    --ink: var(--color-text);
    --muted: var(--color-text-muted);
    --line: var(--color-border);
    --old: var(--color-text-muted);
    --old-soft: var(--color-surface-muted);
    --cmp: var(--color-text-subtle);
    --cmp-soft: var(--color-surface-muted);
    --title: var(--color-accent);
    --ind: var(--color-accent);
    --ind-soft: var(--color-accent-soft);
    --info: var(--color-info);
    --info-soft: var(--color-info-soft);
    --radical: var(--color-info);
    --radical-soft: var(--color-info-soft);
    --warn: var(--color-warning);
    --warn-soft: var(--color-warning-soft);
    --gold: var(--color-warning);
    --gold-soft: var(--color-warning-soft);
    --neu: var(--color-warning);
    --neu-soft: var(--color-warning-soft);
    --fed: var(--color-danger);
    --fed-soft: var(--color-danger-soft);
    --sb: var(--color-success);
    --sb-deep: var(--color-success);
    --sb-soft: var(--color-success-soft);
    --demo: var(--color-success);
    --demo-soft: var(--color-success-soft);
}
:root[data-app-theme="light"] { color-scheme: light; }
:root[data-app-theme] .tab[aria-selected="true"] { color: var(--color-accent-contrast); }
:root[data-app-theme] body,
:root[data-app-theme] body * { font-family: var(--font-sans) !important; }
:root[data-app-theme] body h1,
:root[data-app-theme] body h2 { font-family: var(--font-display) !important; }
</style>
<script>
    (function () {
        var root = document.documentElement;

        addEventListener('message', function (event) {
            if (event.source !== parent || ! event.data || event.data.type !== 'app-theme') {
                return;
            }

            var tokens = event.data.tokens || {};

            for (var name in tokens) {
                if (name.indexOf('--') === 0) {
                    root.style.setProperty(name, tokens[name]);
                }
            }

            root.dataset.theme = event.data.theme === 'light' ? 'light' : 'dark';
            root.dataset.appTheme = root.dataset.theme;
        });

        parent.postMessage({ type: 'note:ready' }, '*');
    })();
</script>
