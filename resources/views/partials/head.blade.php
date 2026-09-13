<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>{{ isset($title) ? $title.' · '.config('app.name') : config('app.name') }}</title>

@isset($description)
    <meta name="description" content="{{ $description }}">
@endisset

<link rel="icon" href="/favicon.ico" sizes="32x32">

{{-- Keeps the browser chrome (address bar, form controls) in step with the
     theme. A `theme-color` meta cannot see `[data-theme]`, so it can only ever
     describe one mode; it describes the default, which is dark. A visitor who
     explicitly picks light gets dark browser chrome for the session — not
     fixable without a server-side cookie. --}}
<meta name="theme-color" content="#131211">

@include('partials.theme-script')

{{-- Paints the ground before the stylesheet arrives. In `npm run dev` Vite
     injects CSS via JS after this script, so without these two rules every
     load flashes white — invisible on a light default, glaring on a dark one.
     This is the one place a background may be set on <html>; the compiled
     stylesheet leaves <html> alone, so body's own background still propagates
     to the canvas the moment app.css lands. --}}
<style>html{background:#131211;color-scheme:dark}html[data-theme='light']{background:#faf8f4;color-scheme:light}</style>

@vite(['resources/scss/app.scss', 'resources/js/app.js'])

@stack('head')
