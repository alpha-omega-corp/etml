@props([
    'items' => [],   // [['label' => 'Projects', 'href' => '/projects'], ['label' => 'Acme']]
    'heading' => false,  // render the last crumb as the page's <h1>, for pages that carry no separate title
])

<nav {{ $attributes->class('breadcrumbs') }} aria-label="Breadcrumb">
    <ol class="breadcrumbs__list">
        @foreach ($items as $item)
            @php
                $isLast = $loop->last;
                $tag = $isLast && $heading ? 'h1' : 'span';
            @endphp

            <li class="breadcrumbs__item">
                @if (! $isLast && ($item['href'] ?? null))
                    <a href="{{ $item['href'] }}" class="breadcrumbs__link">{{ $item['label'] }}</a>
                @else
                    <{{ $tag }} class="breadcrumbs__current" @if ($isLast) aria-current="page" @endif>{{ $item['label'] }}</{{ $tag }}>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
