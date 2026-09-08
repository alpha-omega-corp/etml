@props([
    'paginator',
])

{{--
    Renders a Laravel paginator with this project's styles.

        <x-ui.pagination :paginator="$users" />

    `$paginator` must be a LengthAwarePaginator (or Paginator) — pass the result
    of `->paginate()` straight through.
--}}

@if ($paginator->hasPages())
    <nav {{ $attributes->class('pagination') }} aria-label="Pagination">
        <p class="pagination__summary">
            Showing {{ $paginator->firstItem() }}&ndash;{{ $paginator->lastItem() }}
            @if (method_exists($paginator, 'total'))
                of {{ number_format($paginator->total()) }}
            @endif
        </p>

        <div class="pagination__list">
            @if ($paginator->onFirstPage())
                <span class="pagination__link pagination__link--disabled" aria-hidden="true">
                    <x-ui.icon name="chevron-left" />
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="pagination__link" rel="prev">
                    <span class="visually-hidden">Previous page</span>
                    <x-ui.icon name="chevron-left" />
                </a>
            @endif

            @if (method_exists($paginator, 'links'))
                @foreach ($paginator->links() as $link)
                    @if ($link->url === null)
                        <span class="pagination__link pagination__link--disabled">{{ $link->label }}</span>
                    @elseif ($link->active)
                        <a href="{{ $link->url }}" class="pagination__link pagination__link--current" aria-current="page">{{ $link->label }}</a>
                    @else
                        <a href="{{ $link->url }}" class="pagination__link">{{ $link->label }}</a>
                    @endif
                @endforeach
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="pagination__link" rel="next">
                    <span class="visually-hidden">Next page</span>
                    <x-ui.icon name="chevron-right" />
                </a>
            @else
                <span class="pagination__link pagination__link--disabled" aria-hidden="true">
                    <x-ui.icon name="chevron-right" />
                </span>
            @endif
        </div>
    </nav>
@endif
