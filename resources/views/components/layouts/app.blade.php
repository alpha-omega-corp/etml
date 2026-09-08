@props([
    'title' => null,
    'description' => null,
])

{{--
    The default page shell: header, main content, footer.

        <x-layouts.app title="Dashboard">
            <x-slot:header>...optional page heading...</x-slot:header>
            ...content...
        </x-layouts.app>
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => $title, 'description' => $description])
</head>
<body class="app-body">
    <a href="#main" class="skip-link">Skip to content</a>

    @include('partials.header')

    <main id="main" class="app-main">
        {{ $slot }}
    </main>

    @include('partials.footer')
    @include('partials.flash')
</body>
</html>
