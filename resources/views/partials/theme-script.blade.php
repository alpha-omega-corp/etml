{{--
    Applies the stored theme and accent before the first paint so the page never
    flashes the wrong colours. Must stay inline and stay in <head> — an external
    module would load too late. Keep it tiny; the rest lives in
    resources/js/components/theme.js and resources/js/components/accent.js.
--}}
<script>
    (function () {
        try {
            var theme = localStorage.getItem('theme');

            if (theme === 'light' || theme === 'dark') {
                document.documentElement.dataset.theme = theme;
            }

            var accent = localStorage.getItem('accent');

            if (['green', 'orange', 'blue', 'purple'].indexOf(accent) !== -1) {
                document.documentElement.dataset.accent = accent;
            }
        } catch (e) {
            // Site data blocked. Nothing is stamped on <html>, which is
            // exactly the dark default the bare `:root` block paints, and the
            // default accent still applies.
        }
    })();
</script>
