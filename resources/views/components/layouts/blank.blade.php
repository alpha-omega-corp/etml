@props([
    'title' => null,
    'description' => null,
])

{{--
    A chrome-free shell for pages that stand on their own: sign-in, onboarding,
    error pages, print views. Content is centred in a narrow column.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => $title, 'description' => $description])
</head>
<body class="app-body app-body--centered">
    <main id="main" class="centered-panel">
        <a href="{{ route('home') }}" class="centered-panel__brand">
            <x-ui.icon name="layers" size="22" />
            {{ config('app.name') }}
        </a>

        {{ $slot }}
    </main>

    @include('partials.flash')
</body>
</html>
