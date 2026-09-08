<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>{{ isset($title) ? $title.' · '.config('app.name') : config('app.name') }}</title>

@isset($description)
    <meta name="description" content="{{ $description }}">
@endisset

<link rel="icon" href="/favicon.ico" sizes="32x32">

{{-- Keeps the browser chrome (address bar, form controls) in step with the theme. --}}
<meta name="theme-color" content="#faf8f4" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#131211" media="(prefers-color-scheme: dark)">

@include('partials.theme-script')

@vite(['resources/scss/app.scss', 'resources/js/app.js'])

@stack('head')
