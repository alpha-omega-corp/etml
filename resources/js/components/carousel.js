/**
 * A carousel, as plainly as Alpine allows: one card on screen, an index, and
 * two buttons that move it.
 *
 *   <div x-data="carousel({ start: 3, count: 12 })">
 *       <button x-on:click="prev()" x-bind:disabled="atStart">…</button>
 *       <div class="carousel__viewport">
 *           <ol x-bind:style="track">…</ol>
 *       </div>
 *       <button x-on:click="next()" x-bind:disabled="atEnd">…</button>
 *   </div>
 *
 * Nothing scrolls. The component owns one number — which card is leftmost —
 * and publishes it as `--index`; the stylesheet turns that into the shift.
 *
 * How many cards are on screen is a layout question, so the stylesheet answers
 * it too, in `--per-view`. This reads the value back rather than keeping its
 * own copy of the breakpoints: two sources for one number is how a carousel
 * ends up a card short of its end on one screen size and not another.
 *
 * `start` is which card to open on — for the programme, the next test. It is
 * brought to the MIDDLE of the visible run, not its left edge.
 */
export default function carousel({ start = 0, count = 0 } = {}) {
    return {
        count,
        start,
        perView: 1,
        index: 0,

        // The track starts on card 1 until the opening index is worked out.
        // Without this the first paint animates the whole way there, which
        // reads as the page moving under you.
        ready: false,

        init() {
            this.measure();

            requestAnimationFrame(() => {
                this.ready = true;
            });
        },

        /**
         * Read how many cards the stylesheet is showing, then put the opening
         * card in the middle of them. Also runs on resize: crossing a
         * breakpoint changes what the last valid index is, and an index left
         * past it would park the track on empty space.
         */
        measure() {
            const perView = parseInt(getComputedStyle(this.$el).getPropertyValue('--per-view'), 10);

            this.perView = Number.isFinite(perView) && perView > 0 ? perView : 1;

            if (this.index === 0) {
                this.index = this.start - Math.floor((this.perView - 1) / 2);
            }

            this.index = this.clamp(this.index);
        },

        clamp(index) {
            return Math.min(Math.max(index, 0), Math.max(this.count - this.perView, 0));
        },

        get atStart() {
            return this.index <= 0;
        },

        get atEnd() {
            return this.index >= this.count - this.perView;
        },

        get position() {
            const last = Math.min(this.index + this.perView, this.count);

            return this.perView > 1
                ? `${this.index + 1}–${last} / ${this.count}`
                : `${this.index + 1} / ${this.count}`;
        },

        get track() {
            return `--index: ${this.index}`;
        },

        prev() {
            this.index = this.clamp(this.index - 1);
        },

        next() {
            this.index = this.clamp(this.index + 1);
        },
    };
}
