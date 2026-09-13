/**
 * Adding and editing one line of a programme.
 *
 * There is a single form on the page and two ways in: « Ajouter une date »
 * empties it, the pencil on a line fills it from that line's data attributes.
 * The hidden `entry_id` is what tells the server which of the two it is, so
 * nothing about the endpoint changes between them.
 *
 * The click listener runs in the CAPTURE phase on purpose: `modal.js` opens
 * the dialog on the same click, and the fields have to hold the right values
 * before it does, or the previous line's values flash up first.
 */
const TITLES = {
    create: 'Nouvelle date',
    edit: 'Modifier la date',
};

function form() {
    return document.querySelector('[data-entry-form]');
}

function field(name) {
    return document.querySelector('[data-entry-field="' + name + '"]');
}

function setTitle(mode) {
    const heading = document.getElementById('entry-title');

    if (heading) {
        heading.textContent = TITLES[mode];
    }
}

/**
 * Today, as the date input wants it. Not `toISOString()`, which is UTC: an
 * evening in Switzerland is already tomorrow there.
 */
function today() {
    const now = new Date();

    return new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
}

function fill({ id = '', date = '', title = '', note = '', units = [] }) {
    field('id').value = id;
    field('date').value = date;
    field('title').value = title;
    field('note').value = note;

    document.querySelectorAll('[data-entry-unit]').forEach((box) => {
        box.checked = units.includes(box.value);
    });

    // A rejected save leaves its messages behind; they do not describe the
    // line the user is opening now.
    form().querySelectorAll('.field__error').forEach((message) => message.remove());
}

export function init() {
    if (! form()) {
        return;
    }

    document.addEventListener('click', (event) => {
        if (event.target.closest('[data-entry-new]')) {
            fill({ date: today() });
            setTitle('create');

            return;
        }

        const edit = event.target.closest('[data-entry-edit]');

        if (! edit) {
            return;
        }

        const item = edit.closest('[data-entry]');

        fill({
            id: item.dataset.entryId,
            date: item.dataset.entryDate,
            title: item.dataset.entryTitle,
            note: item.dataset.entryNote,
            units: item.dataset.entryUnits ? item.dataset.entryUnits.split(',') : [],
        });

        setTitle('edit');
    }, true);

    // The server sends us back here when a save is refused: reopen on what was
    // typed, errors and all.
    const dialog = document.querySelector('[data-entry-reopen]');

    if (dialog) {
        setTitle(field('id').value ? 'edit' : 'create');
        dialog.showModal();
    }
}
