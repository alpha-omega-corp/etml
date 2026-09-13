/**
 * Renaming a unit in place.
 *
 * The pencil appears on hover, swaps the name for a field, and the name is
 * saved when the user commits (Enter) or leaves the field. Escape cancels.
 * The row carries its own endpoint in `data-rename-url`, so this works for a
 * chapter and a verb page alike.
 */
const MIN_LENGTH = 2;

function token() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

function start(item) {
    const input = item.querySelector('[data-rename-input]');

    input.value = item.dataset.name;
    input.hidden = false;
    item.classList.add('is-editing');
    input.focus();
    input.select();
}

function end(item, commit) {
    if (! item.classList.contains('is-editing')) {
        return;
    }

    const input = item.querySelector('[data-rename-input]');
    const name = input.value.trim();

    item.classList.remove('is-editing');
    input.hidden = true;

    if (! commit || name === item.dataset.name) {
        return;
    }

    // Same floor as the server, so an obvious mistake never costs a round trip.
    if (name.length < MIN_LENGTH) {
        flagError(item);

        return;
    }

    save(item, name);
}

async function save(item, name) {
    const previous = item.dataset.name;

    apply(item, name);

    try {
        const response = await fetch(item.dataset.renameUrl, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token(),
            },
            body: JSON.stringify({ rename: name }),
        });

        if (! response.ok) {
            throw new Error('HTTP ' + response.status);
        }

        apply(item, (await response.json()).name);
    } catch (error) {
        apply(item, previous);
        flagError(item);
    }
}

function apply(item, name) {
    item.dataset.name = name;
    item.querySelector('.nav-link__label').textContent = name;
    item.querySelector('[data-rename-input]').value = name;
    item.querySelector('[data-rename-edit]').setAttribute('aria-label', 'Renommer « ' + name + ' »');

    // The page title names the unit on screen, so it follows along.
    if (item.querySelector('.nav-link').classList.contains('is-current')) {
        const title = document.querySelector('.deck-head__title');

        if (title) {
            title.textContent = name;
        }
    }
}

function flagError(item) {
    item.classList.add('has-error');
    setTimeout(() => item.classList.remove('has-error'), 1200);
}

export function init() {
    document.addEventListener('click', (event) => {
        const edit = event.target.closest('[data-rename-edit]');

        if (edit) {
            event.preventDefault();
            start(edit.closest('[data-rename]'));
        }
    });

    document.addEventListener('keydown', (event) => {
        const input = event.target.closest('[data-rename-input]');

        if (! input) {
            return;
        }

        if (event.key === 'Enter') {
            event.preventDefault();
            end(input.closest('[data-rename]'), true);
        }

        if (event.key === 'Escape') {
            event.preventDefault();
            end(input.closest('[data-rename]'), false);
        }
    }, true);

    document.addEventListener('focusout', (event) => {
        const input = event.target.closest('[data-rename-input]');

        if (input) {
            end(input.closest('[data-rename]'), true);
        }
    });
}
