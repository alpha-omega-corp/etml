@props([
    'items' => [],   // [['label' => 'Projects', 'href' => '/projects'], ['label' => 'Acme']]
])

<nav {{ $attributes->class('breadcrumbs') }} aria-label="Breadcrumb">
    <ol class="breadcrumbs__list">
        @foreach ($items as $item)
            @php $isLast = $loop->last; @endphp

            <li class="breadcrumbs__item">
                @if (! $isLast && ($item['href'] ?? null))
                    <a href="{{ $item['href'] }}" class="breadcrumbs__link">{{ $item['label'] }}</a>
                @else
                    <span class="breadcrumbs__current" @if ($isLast) aria-current="page" @endif>{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
