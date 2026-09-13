/**
 * The card game.
 *
 * Everything the deck does — flipping, ordering, filtering, marking, the
 * celebrations — lives here and knows nothing about which language it is
 * playing. The page hands it a deck through `<script id="deck-data">`:
 *
 *   {
 *     "cards":    [{ "id": 1, "term": "das Haus", "translation": "la maison", "ex": null }],
 *     "states":   { "1": "known" },
 *     "labels":   { "native": "Français", "target": "Deutsch",
 *                   "nativeShort": "FR", "targetShort": "DE" },
 *     "statusUrl": "/cards/__ID__/status"
 *   }
 *
 * `term` is the word in the language being learnt, `translation` the French
 * side. Swap those two strings and the same module teaches any language.
 */

const FX_BITS = 18;
const BITS = 24;
const SWIPE = 60;

const el = (id) => document.getElementById(id);

export function init() {
    const data = el('deck-data');

    if (! data) {
        return;
    }

    play(JSON.parse(data.textContent));
}

function play(config) {
    const deck = config.cards;
    const labels = config.labels;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    let order = deck.map((_, i) => i);
    let pos = 0;
    let flipped = false;
    let direction = 'native';
    let filterState = 'all';

    const knownSet = new Set();
    const reviewSet = new Set();

    // Restore the session: the marks come from the database, by card id.
    deck.forEach((card, i) => {
        if (config.states[card.id] === 'known') knownSet.add(i);
        if (config.states[card.id] === 'review') reviewSet.add(i);
    });

    const cardEl = el('card');
    const frontLang = el('frontLang');
    const frontWord = el('frontWord');
    const frontEmpty = el('frontEmpty');
    const frontMark = el('frontMark');
    const backLang = el('backLang');
    const backWord = el('backWord');
    const example = el('example');
    const posLabel = el('posLabel');
    const progressFill = el('progressFill');
    const knownCount = el('knownCount');
    const reviewCount = el('reviewCount');
    const syncNote = el('syncNote');
    const filterButtons = document.querySelectorAll('[data-filter]');
    const directionButtons = document.querySelectorAll('[data-direction]');

    function note(message) {
        syncNote.textContent = message || '';
    }

    // Persist one card's status ('known' | 'review' | null to clear).
    async function persist(index, status) {
        try {
            const response = await fetch(config.statusUrl.replace('__ID__', deck[index].id), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ status }),
            });

            if (! response.ok) throw new Error('HTTP ' + response.status);

            note('');
        } catch (error) {
            note('Enregistrement impossible.');
        }
    }

    function setPressed(button, on) {
        button.setAttribute('aria-pressed', on ? 'true' : 'false');
    }

    // --- direction ----------------------------------------------------------

    function setDirection(value) {
        direction = value;
        directionButtons.forEach((button) => setPressed(button, button.dataset.direction === value));
        render();
    }

    directionButtons.forEach((button) => {
        button.onclick = () => setDirection(button.dataset.direction);
    });

    // --- filters ------------------------------------------------------------

    function listForFilter(filter) {
        if (filter === 'known') return Array.from(knownSet);
        if (filter === 'review') return Array.from(reviewSet);

        return deck.map((_, i) => i);
    }

    function setFilter(filter) {
        filterState = filter;
        filterButtons.forEach((button) => setPressed(button, button.dataset.filter === filter));
        order = listForFilter(filter);
        pos = 0;
        render();
    }

    filterButtons.forEach((button) => {
        button.onclick = () => setFilter(button.dataset.filter);
    });

    // Recompute the order after a status change, keeping a valid position.
    function refreshOrderForFilter() {
        order = listForFilter(filterState);

        if (pos >= order.length) {
            pos = Math.max(0, order.length - 1);
        }

        render();
    }

    // --- rendering ----------------------------------------------------------

    function renderCounts() {
        knownCount.textContent = knownSet.size;
        reviewCount.textContent = reviewSet.size;
    }

    function renderMark(index) {
        frontMark.replaceChildren();

        const kind = knownSet.has(index) ? 'known' : (reviewSet.has(index) ? 'review' : null);

        if (! kind) return;

        const badge = document.createElement('span');
        badge.className = 'badge badge--' + (kind === 'known' ? 'success' : 'warning');
        badge.textContent = kind === 'known' ? 'Connu' : 'À revoir';
        frontMark.append(badge);
    }

    // Put the card face up WITHOUT animating: otherwise the flip back plays
    // while the next word is already written on both faces, and you catch a
    // glimpse of it.
    function resetFlip() {
        if (flipped) {
            cardEl.classList.add('is-flip-instant');
            cardEl.classList.remove('is-flipped');
            void cardEl.offsetWidth; // force the reflow before the transition returns
            cardEl.classList.remove('is-flip-instant');
        }

        flipped = false;
    }

    function render() {
        resetFlip();

        if (order.length === 0) {
            // A filter can leave nothing to play. The face then carries the
            // glyph alone — its `aria-label` still says « Liste vide » out loud.
            frontEmpty.hidden = false;
            frontLang.textContent = '';
            frontWord.textContent = '';
            frontMark.replaceChildren();
            backLang.textContent = '';
            backWord.textContent = '';
            example.textContent = '';
            posLabel.textContent = '0 / 0';
            progressFill.style.width = '0%';

            return;
        }

        frontEmpty.hidden = true;

        const index = order[pos];
        const card = deck[index];

        if (direction === 'target') {
            frontLang.textContent = labels.target;
            frontWord.textContent = card.term;
            backLang.textContent = labels.native;
            backWord.textContent = card.translation;
        } else {
            frontLang.textContent = labels.native;
            frontWord.textContent = card.translation;
            backLang.textContent = labels.target;
            backWord.textContent = card.term;
        }

        example.textContent = card.ex || '';
        renderMark(index);
        posLabel.textContent = (pos + 1) + ' / ' + order.length;
        progressFill.style.width = (pos / (order.length - 1 || 1) * 100) + '%';
    }

    // --- moving through the deck --------------------------------------------

    function flip() {
        if (order.length === 0) return;

        fxAnims.forEach((animation) => animation.cancel());
        fxAnims.length = 0;
        flipped = ! flipped;
        cardEl.classList.toggle('is-flipped', flipped);
    }

    function next() {
        if (pos < order.length - 1) {
            pos++;
            render();
        }
    }

    function prev() {
        if (pos > 0) {
            pos--;
            render();
        }
    }

    cardEl.addEventListener('click', flip);
    el('btnNext').onclick = next;
    el('btnPrev').onclick = prev;

    // --- shuffle: the card shakes and drops confetti -------------------------

    const fx = el('shuffleFx');
    const fxIcon = fx.querySelector('.icon');
    const fxBits = [];
    const fxAnims = [];

    for (let i = 0; i < FX_BITS; i++) {
        const bit = document.createElement('span');
        bit.className = 'shuffle-fx__bit shuffle-fx__bit--' + (i % 3);
        fx.append(bit);
        fxBits.push(bit);
    }

    function celebrateShuffle() {
        el('shuffleSay').textContent = order.length + ' cartes mélangées.';

        fxAnims.forEach((animation) => animation.cancel());
        fxAnims.length = 0;

        const keep = (animation) => { fxAnims.push(animation); return animation; };

        if (reduceMotion.matches) {
            // No movement: the icon appears and fades, that is all.
            keep(fxIcon.animate([{ opacity: 0 }, { opacity: 1, offset: 0.2 }, { opacity: 1, offset: 0.7 }, { opacity: 0 }], { duration: 900 }));

            return;
        }

        keep(cardEl.animate([
            { transform: 'translateX(0) rotate(0deg)' },
            { transform: 'translateX(-11px) rotate(-3.6deg)', offset: 0.12 },
            { transform: 'translateX(10px) rotate(3.1deg)', offset: 0.28 },
            { transform: 'translateX(-8px) rotate(-2.3deg)', offset: 0.44 },
            { transform: 'translateX(6px) rotate(1.7deg)', offset: 0.6 },
            { transform: 'translateX(-3px) rotate(-0.9deg)', offset: 0.78 },
            { transform: 'translateX(0) rotate(0deg)' },
        ], { duration: 660, easing: 'cubic-bezier(.32,.7,.35,1)' }));

        keep(fxIcon.animate([
            { transform: 'scale(0.3) rotate(-28deg)', opacity: 0 },
            { transform: 'scale(1.3) rotate(10deg)', opacity: 1, offset: 0.24 },
            { transform: 'scale(1) rotate(0deg)', opacity: 1, offset: 0.58 },
            { transform: 'scale(0.86) rotate(4deg)', opacity: 0 },
        ], { duration: 820, easing: 'cubic-bezier(.22,.9,.3,1)' }));

        fxBits.forEach((bit, i) => {
            const angle = (i / FX_BITS) * Math.PI * 2 + (Math.random() - 0.5) * 0.9;
            const distance = 40 + Math.pow(Math.random(), 1.6) * 110;
            const spin = (Math.random() - 0.5) * 640;

            keep(bit.animate([
                { transform: 'translate3d(0, 0, 0) scale(0.3) rotate(0deg)', opacity: 0 },
                {
                    transform: 'translate3d(' + (Math.cos(angle) * distance * 0.5) + 'px, ' + (Math.sin(angle) * distance * 0.5) + 'px, 0) scale(1.1) rotate(' + (spin * 0.35) + 'deg)',
                    opacity: 1,
                    offset: 0.22,
                },
                {
                    transform: 'translate3d(' + (Math.cos(angle) * distance) + 'px, ' + (Math.sin(angle) * distance + 26) + 'px, 0) scale(0.5) rotate(' + spin + 'deg)',
                    opacity: 0,
                },
            ], {
                duration: 620 + Math.random() * 460,
                delay: Math.random() * 90,
                easing: 'cubic-bezier(.16,.84,.44,1)',
            }));
        });
    }

    el('btnShuffle').onclick = () => {
        if (order.length === 0) return;

        for (let i = order.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [order[i], order[j]] = [order[j], order[i]];
        }

        pos = 0;
        render();
        celebrateShuffle();
    };

    // --- the decision buttons' visual feedback -------------------------------
    //
    // The particles are created once and reused: a press allocates nothing,
    // leaves nothing behind, and a repeated press simply cancels the running
    // animations instead of stacking them.

    const decisionButtons = { known: el('btnKnown'), review: el('btnReview') };
    const bursts = {};
    const runningAnims = { known: [], review: [] };
    const litTimers = {};

    Object.entries(decisionButtons).forEach(([kind, button]) => {
        const host = button.querySelector('.burst');
        const bits = [];

        for (let i = 0; i < BITS; i++) {
            const bit = document.createElement('span');
            bit.className = 'burst__bit' + (i % 3 === 1 ? ' burst__bit--bar' : '') + (i % 4 === 2 ? ' burst__bit--spark' : '');
            host.append(bit);
            bits.push(bit);
        }

        bursts[kind] = { bits, flash: button.querySelector('.btn__flash'), glyph: button.querySelector('svg') };
    });

    function celebrate(kind) {
        const button = decisionButtons[kind];
        const parts = bursts[kind];

        // The colour depends on no animation: it is what carries the meaning.
        button.classList.add('is-lit');
        clearTimeout(litTimers[kind]);
        litTimers[kind] = setTimeout(() => button.classList.remove('is-lit'), 620);

        if (reduceMotion.matches) return;

        runningAnims[kind].forEach((animation) => animation.cancel());
        runningAnims[kind] = [];

        const keep = (animation) => { runningAnims[kind].push(animation); return animation; };

        // The button squashes, then bounces past its resting size.
        keep(button.animate([
            { transform: 'scale(1)' },
            { transform: 'scale(0.76) skewX(2deg)', offset: 0.13 },
            { transform: 'scale(1.28) skewX(-1deg)', offset: 0.35 },
            { transform: 'scale(0.93)', offset: 0.58 },
            { transform: 'scale(1.07)', offset: 0.76 },
            { transform: 'scale(0.98)', offset: 0.89 },
            { transform: 'scale(1)' },
        ], { duration: 620, easing: 'cubic-bezier(.22,.9,.3,1)' }));

        // The icon composes with the button: it peaks far higher than the
        // button does, which gives the spring without inflating the box.
        keep(parts.glyph.animate([
            { transform: 'scale(1) rotate(0deg)' },
            { transform: 'scale(0.4) rotate(-22deg)', offset: 0.11 },
            { transform: 'scale(2.15) rotate(18deg)', offset: 0.34 },
            { transform: 'scale(0.82) rotate(-8deg)', offset: 0.6 },
            { transform: 'scale(1.08) rotate(3deg)', offset: 0.8 },
            { transform: 'scale(1) rotate(0deg)' },
        ], { duration: 660, easing: 'cubic-bezier(.22,.9,.3,1)' }));

        keep(parts.flash.animate([
            { opacity: 0 },
            { opacity: 0.55, offset: 0.06 },
            { opacity: 0 },
        ], { duration: 380, easing: 'ease-out' }));

        parts.bits.forEach((bit, i) => {
            // One sector per particle so the burst stays balanced all around,
            // but a jitter almost as wide as the sector: no two neighbours
            // ever leave at the same angle.
            const sector = (i / BITS) * Math.PI * 2;
            const angle = sector + (Math.random() - 0.5) * (Math.PI * 2 / BITS) * 1.9;

            // It is the spread of the radii that breaks the ring. Squaring the
            // random packs most particles near the button and throws a few far
            // out: a dense core and some shards, never a crown.
            const distance = 18 + Math.pow(Math.random(), 2) * 124;
            const x = Math.cos(angle) * distance;
            const y = Math.sin(angle) * distance * 0.82;

            const spin = (Math.random() - 0.5) * 540;
            const size = 0.45 + Math.random() * 1.2;
            const peak = 0.1 + Math.random() * 0.12;

            keep(bit.animate([
                { transform: 'translate3d(0, 0, 0) scale(0.25) rotate(0deg)', opacity: 0 },
                {
                    transform: 'translate3d(' + (x * 0.6) + 'px, ' + (y * 0.6) + 'px, 0) scale(' + (size * 1.5).toFixed(2) + ') rotate(' + (spin * 0.3) + 'deg)',
                    opacity: 1,
                    offset: peak,
                },
                {
                    transform: 'translate3d(' + x + 'px, ' + y + 'px, 0) scale(' + (size * 0.2).toFixed(2) + ') rotate(' + spin + 'deg)',
                    opacity: 0,
                },
            ], {
                // Very different durations: at any instant the particles are
                // never all at the same radius.
                duration: 380 + Math.random() * 620,
                delay: Math.random() * 80,
                easing: 'cubic-bezier(.12,.86,.36,1)',
            }));
        });
    }

    function mark(kind) {
        if (order.length === 0) return;

        const index = order[pos];

        (kind === 'known' ? knownSet : reviewSet).add(index);
        (kind === 'known' ? reviewSet : knownSet).delete(index);

        persist(index, kind);
        renderCounts();
        celebrate(kind);

        if (filterState === 'all') {
            renderMark(index);
            next();
        } else {
            refreshOrderForFilter();
        }
    }

    el('btnKnown').onclick = () => mark('known');
    el('btnReview').onclick = () => mark('review');

    // The header's « effacer mes listes » has already spoken to the server.
    document.addEventListener('cards:reset', () => {
        knownSet.clear();
        reviewSet.clear();
        renderCounts();
        setFilter('all');
        note('');
    });

    // --- keyboard and touch --------------------------------------------------

    document.addEventListener('keydown', (event) => {
        if (event.target.matches('input, textarea')) return;
        if (event.key === 'ArrowRight') next();
        if (event.key === 'ArrowLeft') prev();
        if (event.key === ' ') {
            event.preventDefault();
            flip();
        }
    });

    // A horizontal swipe is the most natural way to move on with a finger.
    let touchX = null;

    cardEl.addEventListener('touchstart', (event) => {
        touchX = event.changedTouches[0].clientX;
    }, { passive: true });

    cardEl.addEventListener('touchend', (event) => {
        if (touchX === null) return;

        const dx = event.changedTouches[0].clientX - touchX;
        touchX = null;

        if (Math.abs(dx) < SWIPE) return;

        dx < 0 ? next() : prev();
    }, { passive: true });

    render();
    renderCounts();
    vocabulary(deck);
}

/**
 * The whole list, flat and alphabetical by the French side.
 */
function vocabulary(deck) {
    const template = el('vocabRows');

    if (! template) {
        return;
    }

    const host = template.parentElement;
    const rows = [];

    // The search ignores accents and ligatures: « etre » finds « l'être »,
    // « madchen » finds « das Mädchen », « soeur » finds « la sœur » and
    // « gross » finds « groß ». Ligatures do not decompose in NFD, hence the
    // table.
    const LIGATURES = { 'œ': 'oe', 'æ': 'ae', 'ß': 'ss' };

    const fold = (value) => value
        .toLowerCase()
        .replace(/[œæß]/g, (char) => LIGATURES[char])
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');

    deck.slice().sort((a, b) => a.translation.localeCompare(b.translation, 'fr')).forEach((card) => {
        const row = document.createElement('tr');
        const translation = document.createElement('td');
        translation.textContent = card.translation;
        const term = document.createElement('td');
        term.textContent = card.term;
        row.append(translation, term);
        row.dataset.search = fold(card.translation + ' ' + card.term);
        host.append(row);
        rows.push(row);
    });

    template.remove();

    const search = el('vocabSearch');
    const count = el('vocabCount');
    const empty = el('vocabEmpty');

    function filter() {
        const query = fold(search.value.trim());
        let shown = 0;

        rows.forEach((row) => {
            const hit = query === '' || row.dataset.search.includes(query);
            row.hidden = ! hit;

            if (hit) shown++;
        });

        count.textContent = shown + ' / ' + rows.length;
        empty.hidden = shown !== 0;
    }

    search.addEventListener('input', filter);
    filter();
}
