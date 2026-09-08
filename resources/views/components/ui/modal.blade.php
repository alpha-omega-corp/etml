@props([
    'id',
    'title' => null,
    'description' => null,
    'wide' => false,
    'static' => false,   // clicking the backdrop no longer closes it
])

{{--
    Opened from anywhere on the page with `data-modal-open="<id>"`:

        <x-ui.button data-modal-open="confirm-delete">Delete</x-ui.button>

        <x-ui.modal id="confirm-delete" title="Delete project?">
            This cannot be undone.
            <x-slot:footer>
                <x-ui.button variant="ghost" data-modal-close>Cancel</x-ui.button>
                <x-ui.button variant="danger">Delete</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>

    Built on <dialog>, so focus trapping, Esc-to-close and stacking are the
    browser's job.
--}}

<dialog
    id="{{ $id }}"
    {{ $attributes->class(['modal', 'modal--wide' => $wide]) }}
    data-modal
    @if ($static) data-modal-static @endif
    @if ($title) aria-labelledby="{{ $id }}-title" @endif
    @if ($description) aria-describedby="{{ $id }}-description" @endif
>
    @if ($title || isset($header))
        <div class="modal__header">
            @isset($header)
                {{ $header }}
            @else
                <div>
                    <h2 id="{{ $id }}-title" class="modal__title">{{ $title }}</h2>

                    @if ($description)
                        <p id="{{ $id }}-description" class="modal__description">{{ $description }}</p>
                    @endif
                </div>
            @endisset

            <button type="button" class="modal__close" data-modal-close="dismiss">
                <span class="visually-hidden">Close</span>
                <x-ui.icon name="x" />
            </button>
        </div>
    @endif

    <div class="modal__body">{{ $slot }}</div>

    @isset($footer)
        <div class="modal__footer">{{ $footer }}</div>
    @endisset
</dialog>
