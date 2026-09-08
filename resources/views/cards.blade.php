<!DOCTYPE html>
<html lang="fr">
<head>
    @include('partials.head')
</head>
<body class="app-body deck-body">

<header class="deck-bar">
    <button
        type="button"
        class="deck-bar__menu"
        id="navToggle"
        aria-controls="subnav"
        aria-expanded="false"
    >
        <span class="visually-hidden">Chapitres</span>
        <x-ui.icon name="menu" size="20" />
    </button>

    <span class="deck-bar__title">MTU</span>

    <div class="cluster push-end" style="--cluster-gap: 0">
        <x-ui.theme-toggle />

        <x-ui.dropdown>
            <x-slot:trigger>
                <button type="button" class="btn btn--ghost deck-bar__user" data-dropdown-trigger aria-expanded="false" aria-haspopup="true">
                    <x-ui.icon name="user" />
                    <span class="deck-bar__name">{{ auth()->user()->username }}</span>
                </button>
            </x-slot:trigger>

            <x-ui.dropdown.item id="btnReset" icon="refresh" variant="danger">Effacer mes listes</x-ui.dropdown.item>
            <x-ui.dropdown.divider />
            <x-ui.dropdown.item type="submit" form="logout-form" icon="log-out">Changer de nom</x-ui.dropdown.item>
            <x-ui.dropdown.divider />
            @if ($isAdmin)
                <x-ui.dropdown.item type="submit" form="admin-logout-form" icon="shield">Quitter le mode admin</x-ui.dropdown.item>
            @else
                <x-ui.dropdown.item href="{{ route('admin.login') }}" icon="lock">Mode administrateur</x-ui.dropdown.item>
            @endif
        </x-ui.dropdown>
    </div>
</header>

<form id="logout-form" method="POST" action="{{ route('logout') }}" hidden>@csrf</form>
@if ($isAdmin)
    <form id="admin-logout-form" method="POST" action="{{ route('admin.logout') }}" hidden>@csrf</form>
@endif

<div class="deck-shell">

<div class="nav-backdrop" id="navBackdrop" aria-hidden="true"></div>

<nav class="subnav" id="subnav" aria-label="Chapitres">
    <div class="subnav__bar">
        <span class="nav-title">Chapitres</span>
        <button type="button" class="subnav__close" id="navClose">
            <span class="visually-hidden">Fermer</span>
            <x-ui.icon name="x" size="18" />
        </button>
    </div>

    @if ($chapters->isEmpty())
        <p class="nav-empty">Aucun chapitre</p>
    @else
        <ul class="nav-list">
            @foreach ($chapters as $item)
                <li
                    class="nav-item"
                    data-chapter="{{ $item->id }}"
                    data-name="{{ $item->name }}"
                >
                    <a
                        href="{{ route('chapters.show', $item) }}"
                        class="nav-link @if ($chapter && $item->id === $chapter->id) is-current @endif"
                        @if ($chapter && $item->id === $chapter->id) aria-current="page" @endif
                    >
                        <span class="nav-link__label">{{ $item->name }}</span>
                        <span class="nav-link__count">{{ $item->cards_count }}</span>
                    </a>

                    <input
                        type="text"
                        class="input nav-item__input"
                        value="{{ $item->name }}"
                        aria-label="Nom du chapitre"
                        data-chapter-input
                        hidden
                    >

                    <button
                        type="button"
                        class="nav-item__edit"
                        data-chapter-edit
                        aria-label="Renommer « {{ $item->name }} »"
                    >
                        <x-ui.icon name="pencil" size="14" />
                    </button>

                    @if ($isAdmin)
                        <form
                            method="POST"
                            action="{{ route('chapters.destroy', $item) }}"
                            class="nav-item__delete"
                            onsubmit="return confirm('Supprimer « {{ $item->name }} » et ses {{ $item->cards_count }} mots ?')"
                        >
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="nav-item__edit nav-item__edit--danger" aria-label="Supprimer « {{ $item->name }} »">
                                <x-ui.icon name="trash" size="14" />
                            </button>
                        </form>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    <a href="{{ route('chapters.create') }}" class="subnav__new">+ Nouveau chapitre</a>
</nav>

<main id="main" class="content">

    @if ($chapter)
        <div class="deck-head">
            <h1 class="deck-head__title">{{ $chapter->name }}</h1>

            @if (count($deck) > 0)
                <div class="dir-pill" role="group" aria-label="Sens de traduction">
                    <button type="button" class="dir-pill__option" id="dirFrDe" aria-pressed="true">FR → DE</button>
                    <button type="button" class="dir-pill__option" id="dirDeFr" aria-pressed="false">DE → FR</button>
                </div>
            @endif
        </div>
    @endif

@if (! $chapter)

    <div class="import">
        <h1 class="import__title">Aucun chapitre</h1>
        <p class="import__lead">
            Créez un chapitre dans la colonne « Chapitres » en y collant votre
            liste de mots au format JSON.
        </p>
    </div>

@elseif (count($deck) === 0)

    <div class="import">
        <h1 class="import__title">{{ $chapter->name }}</h1>
        <p class="import__lead">
            Ce chapitre est vide. Collez la liste de mots au format JSON pour créer
            le jeu de cartes.
        </p>

        <form method="POST" action="{{ route('chapters.import', $chapter) }}" class="stack">
            @csrf

            <x-ui.form.textarea
                name="import"
                label="Mots (JSON)"
                rows="12"
                required
                :placeholder="$wordsPlaceholder"
            />

            @include('partials.json-template')

            <details class="json-template">
                <summary>Formats acceptés</summary>
                <div class="json-template__body">
                    <p>Un objet <strong>français → allemand</strong> :</p>
                    <pre class="json-template__code">{{ $sampleMap }}</pre>
                    <p>Ou une liste, quand une entrée porte un exemple ou un intertitre :</p>
                    <pre class="json-template__code">{{ $sampleList }}</pre>
                    <p>{{ \App\Support\WordList::MAX_ENTRIES }} entrées au maximum.</p>
                </div>
            </details>

            <x-ui.button type="submit" variant="primary" size="lg" block icon="plus">
                Créer les cartes
            </x-ui.button>
        </form>
    </div>

@else

    <div class="deck">

    <div class="deck__tools">
        <button type="button" class="vocab-open" data-modal-open="vocab">
            <x-ui.icon name="book" size="16" />
            <span class="vocab-open__label">Tout le vocabulaire</span>
            <span class="vocab-open__count">{{ count($deck) }}</span>
        </button>

        <x-ui.button type="button" size="sm" icon="shuffle" id="btnShuffle" class="deck__shuffle">Mélanger</x-ui.button>
    </div>

    <div class="deck__filters btn-group" role="group" aria-label="Filtrer les cartes">
        <button type="button" class="btn btn--sm" data-filter="all" aria-pressed="true">Tous</button>
        <button type="button" class="btn btn--sm" data-filter="known" aria-pressed="false">
            Connus <span class="deck__filter-count" id="knownCount">0</span>
        </button>
        <button type="button" class="btn btn--sm" data-filter="review" aria-pressed="false">
            À revoir <span class="deck__filter-count" id="reviewCount">0</span>
        </button>
    </div>

    <div class="deck__stage">
        <button type="button" class="flashcard" id="card" aria-label="Retourner la carte">
            <span class="flashcard__face">
                <span class="flashcard__lang" id="frontLang">Français</span>
                <span class="flashcard__mark" id="frontMark"></span>
                <span class="flashcard__word" id="frontWord"></span>
                <x-ui.icon name="refresh" class="flashcard__flip" size="18" />
            </span>
            <span class="flashcard__face flashcard__face--back">
                <span class="flashcard__lang" id="backLang">Deutsch</span>
                <span class="flashcard__word" id="backWord"></span>
                <span class="flashcard__example" id="example"></span>
            </span>
        </button>

        <span class="shuffle-fx" id="shuffleFx" aria-hidden="true">
            <x-ui.icon name="sparkles" class="shuffle-fx__icon" />
        </span>
    </div>

    <p class="visually-hidden" role="status" id="shuffleSay"></p>

    <div class="deck__meta">
        <span class="deck__position" id="posLabel">1 / {{ count($deck) }}</span>
        <div class="deck__progress"><div class="deck__progress-fill" id="progressFill"></div></div>
    </div>

    <div class="deck__actions">
        <button type="button" class="btn btn--icon" id="btnPrev" aria-label="Carte précédente">
            <x-ui.icon name="chevron-left" />
        </button>

        <div class="deck__decide">
            <button type="button" class="btn btn--decision btn--review" id="btnReview">
                <x-ui.icon name="clock" />
                <span class="btn__label">À revoir</span>
                <span class="btn__flash" aria-hidden="true"></span>
                <span class="burst" aria-hidden="true"></span>
            </button>

            <button type="button" class="btn btn--decision btn--known" id="btnKnown">
                <x-ui.icon name="check" />
                <span class="btn__label">Je sais</span>
                <span class="btn__flash" aria-hidden="true"></span>
                <span class="burst" aria-hidden="true"></span>
            </button>
        </div>

        <button type="button" class="btn btn--icon" id="btnNext" aria-label="Carte suivante">
            <x-ui.icon name="chevron-right" />
        </button>
    </div>


    <p class="deck__sync" id="syncNote" role="status"></p>


    </div>

@endif

</main>

</div>

@if (count($deck) > 0)
    <x-ui.modal id="vocab" title="Tout le vocabulaire" class="modal--full">
        <div class="vocab__search">
            <input
                type="search"
                id="vocabSearch"
                class="input"
                placeholder="Rechercher en français ou en allemand…"
                autocomplete="off"
                autofocus
                aria-label="Rechercher un mot"
            >
            <p class="vocab__count" id="vocabCount"></p>
        </div>

        <x-ui.table :headers="['Français', 'Deutsch']" compact>
            <template id="vocabRows"></template>
        </x-ui.table>

        <p class="vocab__empty" id="vocabEmpty" hidden>Aucun mot ne correspond.</p>
    </x-ui.modal>
@endif

@include('partials.flash')

<script>
// --- tiroir de navigation (téléphone) ----------------------------------------
(function () {
    const toggle = document.getElementById('navToggle');
    const close = document.getElementById('navClose');
    const backdrop = document.getElementById('navBackdrop');
    const subnav = document.getElementById('subnav');
    const roomy = window.matchMedia('(min-width: 64rem)');

    function setNav(open) {
        document.body.classList.toggle('is-nav-open', open);
        toggle.setAttribute('aria-expanded', String(open));

        if (open) {
            subnav.querySelector('a, button')?.focus();
        }
    }

    toggle.addEventListener('click', () => {
        setNav(! document.body.classList.contains('is-nav-open'));
    });

    close.addEventListener('click', () => { setNav(false); toggle.focus(); });
    backdrop.addEventListener('click', () => setNav(false));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && document.body.classList.contains('is-nav-open')) {
            setNav(false);
            toggle.focus();
        }
    });

    // Opening a chapter closes the drawer behind you.
    subnav.addEventListener('click', (event) => {
        if (event.target.closest('a[href]')) {
            setNav(false);
        }
    });

    // Crossing to the wide layout, where the rail is always there.
    roomy.addEventListener('change', () => setNav(false));
})();

// --- renommage direct dans la navigation -------------------------------------
//
// Le crayon apparaît au survol, remplace le nom par un champ, et le nom est
// enregistré quand l'utilisateur valide (Entrée) ou quitte le champ.
const csrf = document.querySelector('meta[name="csrf-token"]').content;

function startRename(item) {
    const input = item.querySelector('[data-chapter-input]');

    input.value = item.dataset.name;
    input.hidden = false;
    item.classList.add('is-editing');
    input.focus();
    input.select();
}

function endRename(item, commit) {
    if (! item.classList.contains('is-editing')) {
        return;
    }

    const input = item.querySelector('[data-chapter-input]');
    const name = input.value.trim();

    item.classList.remove('is-editing');
    input.hidden = true;

    if (! commit || name === item.dataset.name) {
        return;
    }

    // Same floor as the server, so an obvious mistake never costs a round trip.
    if (name.length < 2) {
        flagRenameError(item);

        return;
    }

    saveRename(item, name);
}

async function saveRename(item, name) {
    const previous = item.dataset.name;

    applyRename(item, name);

    try {
        const res = await fetch('/chapters/' + item.dataset.chapter, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({ rename: name }),
        });

        if (! res.ok) {
            throw new Error('HTTP ' + res.status);
        }

        const body = await res.json();
        applyRename(item, body.name);
    } catch (e) {
        applyRename(item, previous);
        flagRenameError(item);
    }
}

function applyRename(item, name) {
    item.dataset.name = name;
    item.querySelector('.nav-link__label').textContent = name;
    item.querySelector('[data-chapter-input]').value = name;
    item.querySelector('[data-chapter-edit]').setAttribute('aria-label', 'Renommer « ' + name + ' »');

    // The page title names the chapter on screen, so it follows along.
    if (item.querySelector('.nav-link').classList.contains('is-current')) {
        const title = document.querySelector('.deck-head__title');
        if (title) {
            title.textContent = name;
        }
    }
}

function flagRenameError(item) {
    item.classList.add('has-error');
    setTimeout(() => item.classList.remove('has-error'), 1200);
}

document.addEventListener('click', (event) => {
    const edit = event.target.closest('[data-chapter-edit]');

    if (edit) {
        event.preventDefault();
        startRename(edit.closest('[data-chapter]'));
    }
});

document.addEventListener('keydown', (event) => {
    const input = event.target.closest('[data-chapter-input]');

    if (! input) {
        return;
    }

    if (event.key === 'Enter') {
        event.preventDefault();
        endRename(input.closest('[data-chapter]'), true);
    }

    if (event.key === 'Escape') {
        event.preventDefault();
        endRename(input.closest('[data-chapter]'), false);
    }
}, true);

document.addEventListener('focusout', (event) => {
    const input = event.target.closest('[data-chapter-input]');

    if (input) {
        endRename(input.closest('[data-chapter]'), true);
    }
});

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-copy]');
    if (!button) return;

    const source = document.getElementById(button.dataset.copy);
    if (!source) return;

    navigator.clipboard.writeText(source.textContent).then(() => {
        const label = button.querySelector('.btn__label') ?? button;
        const original = label.textContent;
        label.textContent = 'Copié';
        setTimeout(() => { label.textContent = original; }, 1600);
    }).catch(() => {});
});

// Les chapitres défilent horizontalement sur téléphone : on amène l'entrée
// courante dans le champ de vision.
document.querySelectorAll('.nav-link.is-current').forEach((link) => {
    const list = link.closest('.nav-list');
    if (list && list.scrollWidth > list.clientWidth) {
        link.scrollIntoView({ inline: 'center', block: 'nearest' });
    }
});
</script>

@if (count($deck) > 0)
<script>
const deck = @json($deck);
const savedStates = @json((object) $states);
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
const resetUrl = @json(route('cards.reset'));

let order = deck.map((_, i) => i);
let pos = 0;
let flipped = false;
let knownSet = new Set();
let reviewSet = new Set();
let direction = 'fr-de';
let filterState = 'all';

// Restaure la session : les statuts viennent de la base, par id de carte.
deck.forEach((item, i) => {
    if (savedStates[item.id] === 'known') knownSet.add(i);
    if (savedStates[item.id] === 'review') reviewSet.add(i);
});

const el = (id) => document.getElementById(id);
const cardEl = el('card');
const frontLang = el('frontLang');
const frontWord = el('frontWord');
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

function note(message) {
    syncNote.textContent = message || '';
}

// Persiste le statut d'une carte ('known' | 'review' | null pour effacer).
async function persist(index, status) {
    try {
        const res = await fetch('/cards/' + deck[index].id + '/status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ status: status }),
        });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        note('');
    } catch (e) {
        note('Enregistrement impossible.');
    }
}

function setPressed(button, on) {
    button.setAttribute('aria-pressed', on ? 'true' : 'false');
}

function setDirection(value) {
    direction = value;
    setPressed(el('dirFrDe'), value === 'fr-de');
    setPressed(el('dirDeFr'), value === 'de-fr');
    render();
}

el('dirFrDe').onclick = () => setDirection('fr-de');
el('dirDeFr').onclick = () => setDirection('de-fr');

function listForFilter(f) {
    if (f === 'known') return Array.from(knownSet);
    if (f === 'review') return Array.from(reviewSet);
    return deck.map((_, i) => i);
}

function setFilter(f) {
    filterState = f;
    filterButtons.forEach((b) => setPressed(b, b.dataset.filter === f));
    order = listForFilter(f);
    pos = 0;
    render();
}

filterButtons.forEach((b) => {
    b.onclick = () => setFilter(b.dataset.filter);
});

// Recalcule l'ordre après un changement de statut, en gardant une position valide.
function refreshOrderForFilter() {
    order = listForFilter(filterState);
    if (pos >= order.length) pos = Math.max(0, order.length - 1);
    render();
}

function renderCounts() {
    knownCount.textContent = knownSet.size;
    reviewCount.textContent = reviewSet.size;
}

function renderMark(index) {
    frontMark.replaceChildren();
    const kind = knownSet.has(index) ? 'known' : (reviewSet.has(index) ? 'review' : null);
    if (!kind) return;
    const badge = document.createElement('span');
    badge.className = 'badge badge--' + (kind === 'known' ? 'success' : 'warning');
    badge.textContent = kind === 'known' ? 'Connu' : 'À revoir';
    frontMark.append(badge);
}

// Remet la carte face avant SANS animation : sinon la rotation de retour joue
// pendant que le mot suivant est déjà écrit sur les deux faces, et on l'aperçoit
// une fraction de seconde.
function resetFlip() {
    if (flipped) {
        cardEl.classList.add('is-flip-instant');
        cardEl.classList.remove('is-flipped');
        void cardEl.offsetWidth; // force le reflow avant de rendre la transition
        cardEl.classList.remove('is-flip-instant');
    }

    flipped = false;
}

function render() {
    resetFlip();

    if (order.length === 0) {
        frontLang.textContent = '';
        frontWord.textContent = 'Liste vide';
        frontMark.replaceChildren();
        backLang.textContent = '';
        backWord.textContent = '';
        example.textContent = '';
        posLabel.textContent = '0 / 0';
        progressFill.style.width = '0%';
        return;
    }

    const index = order[pos];
    const item = deck[index];

    if (direction === 'de-fr') {
        frontLang.textContent = 'Deutsch';
        frontWord.textContent = item.de;
        backLang.textContent = 'Français';
        backWord.textContent = item.fr;
    } else {
        frontLang.textContent = 'Français';
        frontWord.textContent = item.fr;
        backLang.textContent = 'Deutsch';
        backWord.textContent = item.de;
    }

    example.textContent = item.ex || '';
    renderMark(index);
    posLabel.textContent = (pos + 1) + ' / ' + order.length;
    progressFill.style.width = (pos / (order.length - 1 || 1) * 100) + '%';
}

function flip() {
    if (order.length === 0) return;

    fxAnims.forEach((anim) => anim.cancel());
    fxAnims.length = 0;
    flipped = !flipped;
    cardEl.classList.toggle('is-flipped', flipped);
}

cardEl.addEventListener('click', flip);

function next() {
    if (pos < order.length - 1) { pos++; render(); }
}

function prev() {
    if (pos > 0) { pos--; render(); }
}

el('btnNext').onclick = next;
el('btnPrev').onclick = prev;

// --- mélange : la carte se secoue et lâche des confettis ---------------------

const FX_BITS = 18;
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

    fxAnims.forEach((anim) => anim.cancel());
    fxAnims.length = 0;

    const keep = (anim) => { fxAnims.push(anim); return anim; };

    if (reduceMotion.matches) {
        // Pas de mouvement : l'icône paraît puis s'efface, c'est tout.
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

// --- Retour visuel des boutons de décision -----------------------------------
//
// Les particules sont créées une seule fois et réutilisées : un appui n'alloue
// rien, ne laisse rien derrière lui, et un appui répété annule simplement les
// animations en cours au lieu de les empiler.

const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
const BITS = 24;

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

    // La couleur, elle, ne dépend d'aucune animation : c'est elle qui porte le sens.
    button.classList.add('is-lit');
    clearTimeout(litTimers[kind]);
    litTimers[kind] = setTimeout(() => button.classList.remove('is-lit'), 620);

    if (reduceMotion.matches) return;

    runningAnims[kind].forEach((anim) => anim.cancel());
    runningAnims[kind] = [];
    const keep = (anim) => { runningAnims[kind].push(anim); return anim; };

    // Le bouton s'écrase puis rebondit au-delà de sa taille de repos.
    keep(button.animate([
        { transform: 'scale(1)' },
        { transform: 'scale(0.76) skewX(2deg)', offset: 0.13 },
        { transform: 'scale(1.28) skewX(-1deg)', offset: 0.35 },
        { transform: 'scale(0.93)', offset: 0.58 },
        { transform: 'scale(1.07)', offset: 0.76 },
        { transform: 'scale(0.98)', offset: 0.89 },
        { transform: 'scale(1)' },
    ], { duration: 620, easing: 'cubic-bezier(.22,.9,.3,1)' }));

    // L'icône se compose avec le bouton : elle culmine bien plus haut que lui,
    // ce qui donne la détente sans gonfler la boîte.
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
        // Un secteur par particule pour que l'explosion reste équilibrée tout
        // autour, mais un jitter presque aussi large que le secteur : deux
        // voisines ne partent jamais du même angle.
        const sector = (i / BITS) * Math.PI * 2;
        const angle = sector + (Math.random() - 0.5) * (Math.PI * 2 / BITS) * 1.9;

        // C'est la dispersion des rayons qui casse l'anneau. Le carré du hasard
        // tasse la plupart des particules près du bouton et en envoie quelques
        // unes très loin : un noyau dense et des éclats, jamais une couronne.
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
            // Des durées très différentes : à un instant donné les particules
            // ne sont jamais toutes au même rayon.
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

el('btnReset').onclick = async () => {
    if (!confirm('Effacer vos listes ?')) return;
    try {
        const res = await fetch(resetUrl, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        knownSet.clear();
        reviewSet.clear();
        renderCounts();
        setFilter('all');
        note('');
    } catch (e) {
        note('Réinitialisation impossible.');
    }
};

document.addEventListener('keydown', (e) => {
    if (e.target.matches('input, textarea')) return;
    if (e.key === 'ArrowRight') next();
    if (e.key === 'ArrowLeft') prev();
    if (e.key === ' ') { e.preventDefault(); flip(); }
});

// Glissement horizontal : la façon la plus naturelle de passer une carte au doigt.
let touchX = null;
cardEl.addEventListener('touchstart', (e) => { touchX = e.changedTouches[0].clientX; }, { passive: true });
cardEl.addEventListener('touchend', (e) => {
    if (touchX === null) return;
    const dx = e.changedTouches[0].clientX - touchX;
    touchX = null;
    if (Math.abs(dx) < 60) return;
    dx < 0 ? next() : prev();
}, { passive: true });

render();
renderCounts();

// Liste complète (Français → Deutsch), à plat et par ordre alphabétique.
const rowsHost = document.getElementById('vocabRows').parentElement;
const vocabRows = [];

// La recherche ignore les accents et les ligatures : « etre » trouve « l'être »,
// « madchen » trouve « das Mädchen », « soeur » trouve « la sœur » et « gross »
// trouve « groß ». Les ligatures ne se décomposent pas en NFD, d'où la table.
const LIGATURES = { 'œ': 'oe', 'æ': 'ae', 'ß': 'ss' };

const fold = (value) => value
    .toLowerCase()
    .replace(/[œæß]/g, (char) => LIGATURES[char])
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '');

deck.slice().sort((a, b) => a.fr.localeCompare(b.fr, 'fr')).forEach((item) => {
    const row = document.createElement('tr');
    const fr = document.createElement('td');
    fr.textContent = item.fr;
    const de = document.createElement('td');
    de.textContent = item.de;
    row.append(fr, de);
    row.dataset.search = fold(item.fr + ' ' + item.de);
    rowsHost.append(row);
    vocabRows.push(row);
});

document.getElementById('vocabRows').remove();

const vocabSearch = el('vocabSearch');
const vocabCount = el('vocabCount');
const vocabEmpty = el('vocabEmpty');

function filterVocab() {
    const query = fold(vocabSearch.value.trim());
    let shown = 0;

    vocabRows.forEach((row) => {
        const hit = query === '' || row.dataset.search.includes(query);
        row.hidden = ! hit;
        if (hit) shown++;
    });

    vocabCount.textContent = shown + ' / ' + vocabRows.length;
    vocabEmpty.hidden = shown !== 0;
}

vocabSearch.addEventListener('input', filterVocab);
filterVocab();
</script>
@endif

</body>
</html>
