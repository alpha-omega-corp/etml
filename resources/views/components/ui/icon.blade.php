@props([
    'name',
    'size' => null,
    'label' => null,
])

@php
    /**
     * A single inline-SVG icon set. Inline SVG (rather than an icon font or
     * sprite request) keeps icons themeable via `currentColor` and costs no
     * extra HTTP request.
     *
     * All paths are drawn on a 24x24 grid with a 1.6 stroke. To add an icon,
     * append an entry here — keep the key kebab-case and the artwork stroked,
     * not filled, so it inherits colour and weight from its context.
     */
    $icons = [
        'alert-circle' => '<circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/>',
        'alert-triangle' => '<path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><path d="M12 9v4M12 17h.01"/>',
        'arrow-left' => '<path d="M20 12H4"/><path d="M10 6l-6 6 6 6"/>',
        'arrow-right' => '<path d="M4 12h16"/><path d="M14 6l6 6-6 6"/>',
        'arrow-up-right' => '<path d="M7 17L17 7"/><path d="M8 7h9v9"/>',
        'bell' => '<path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/>',
        'bolt' => '<path d="M13 3L5 14h6l-1 7 8-11h-6z"/>',
        'book' => '<path d="M4 5a2 2 0 012-2h13v18H6a2 2 0 01-2-2z"/><path d="M4 17h15"/>',
        'calendar' => '<rect x="3.5" y="5" width="17" height="16" rx="2"/><path d="M3.5 10h17M8 3v4M16 3v4"/>',
        'chart' => '<path d="M3 21h18"/><path d="M6 21V11M11 21V5M16 21v-7M21 21v-4"/>',
        'check' => '<path d="M4.5 12.75l5 5L19.5 7"/>',
        'check-circle' => '<path d="M21 11.08V12a9 9 0 11-5.34-8.23"/><path d="M9 11l3 3L22 4"/>',
        'chevron-down' => '<path d="M6 9.5l6 6 6-6"/>',
        'chevron-left' => '<path d="M14.5 6l-6 6 6 6"/>',
        'chevron-right' => '<path d="M9.5 6l6 6-6 6"/>',
        'chevron-up' => '<path d="M6 14.5l6-6 6 6"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>',
        'code' => '<path d="M9 18l-6-6 6-6"/><path d="M15 6l6 6-6 6"/>',
        'database' => '<ellipse cx="12" cy="6" rx="8" ry="3"/><path d="M20 6v12c0 1.66-3.58 3-8 3s-8-1.34-8-3V6"/><path d="M20 12c0 1.66-3.58 3-8 3s-8-1.34-8-3"/>',
        'dots-vertical' => '<circle cx="12" cy="5" r="1.5" fill="currentColor" stroke="none"/><circle cx="12" cy="12" r="1.5" fill="currentColor" stroke="none"/><circle cx="12" cy="19" r="1.5" fill="currentColor" stroke="none"/>',
        'download' => '<path d="M4 16v3a2 2 0 002 2h12a2 2 0 002-2v-3"/><path d="M8 11l4 4 4-4"/><path d="M12 3v12"/>',
        'external-link' => '<path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><path d="M15 3h6v6"/><path d="M10 14L21 3"/>',
        'eye' => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>',
        'file' => '<path d="M14 3H7a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2V8z"/><path d="M14 3v5h5"/>',
        'filter' => '<path d="M4 5h16l-6 7.5V20l-4-2v-5.5z"/>',
        'folder' => '<path d="M3 7a2 2 0 012-2h4l2 2.5h8a2 2 0 012 2V18a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>',
        'grid' => '<rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/>',
        'home' => '<path d="M3 10.5L12 3l9 7.5"/><path d="M5 9.5V20h14V9.5"/><path d="M10 20v-6h4v6"/>',
        'inbox' => '<path d="M21 12h-6l-2 3h-2l-2-3H3"/><path d="M5.45 5.11L3 12v6a2 2 0 002 2h14a2 2 0 002-2v-6l-2.45-6.89A2 2 0 0016.76 4H7.24a2 2 0 00-1.79 1.11z"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 16v-4M12 8h.01"/>',
        'layers' => '<path d="M12 3l9 5-9 5-9-5z"/><path d="M3 13l9 5 9-5"/>',
        'link' => '<path d="M9.5 14.5l5-5"/><path d="M11 6.5l1.5-1.5a4 4 0 015.5 5.5L16.5 12"/><path d="M13 17.5L11.5 19a4 4 0 01-5.5-5.5L7.5 12"/>',
        'lock' => '<rect x="4.5" y="10.5" width="15" height="10" rx="2"/><path d="M8.5 10.5V7a3.5 3.5 0 017 0v3.5"/>',
        'log-out' => '<path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/>',
        'mail' => '<rect x="2.5" y="5" width="19" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'minus' => '<path d="M5 12h14"/>',
        'monitor' => '<rect x="2.5" y="4" width="19" height="12" rx="2"/><path d="M9 20h6M12 16v4"/>',
        'moon' => '<path d="M20.5 14.8A8.5 8.5 0 019.2 3.5 8.5 8.5 0 1020.5 14.8z"/>',
        'palette' => '<path d="M12 21a9 9 0 110-18c4.97 0 9 3.58 9 8 0 2.21-1.79 4-4 4h-1.5a1.5 1.5 0 00-1.06 2.56A1.5 1.5 0 0112 21z"/><circle cx="7.5" cy="12" r="1.2" fill="currentColor" stroke="none"/><circle cx="10" cy="8" r="1.2" fill="currentColor" stroke="none"/><circle cx="15" cy="8.5" r="1.2" fill="currentColor" stroke="none"/>',
        'pencil' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 013 3L7 19l-4 1 1-4z"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'refresh' => '<path d="M3.5 12a8.5 8.5 0 0114.5-6"/><path d="M18 3v3.5h-3.5"/><path d="M20.5 12a8.5 8.5 0 01-14.5 6"/><path d="M6 21v-3.5h3.5"/>',
        'search' => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="M15.5 15.5L21 21"/>',
        'shuffle' => '<path d="M16 3h5v5"/><path d="M4 20L21 3"/><path d="M21 16v5h-5"/><path d="M15 15l6 6"/><path d="M3 4l5 5"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 00.34 1.87l.06.06a2 2 0 01-2.83 2.83l-.06-.06a1.7 1.7 0 00-1.87-.34 1.7 1.7 0 00-1.03 1.56V21a2 2 0 01-4 0v-.09A1.7 1.7 0 009.4 19.4a1.7 1.7 0 00-1.87.34l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.7 1.7 0 004.6 15a1.7 1.7 0 00-1.56-1.03H3a2 2 0 010-4h.09A1.7 1.7 0 004.6 9.4a1.7 1.7 0 00-.34-1.87l-.06-.06a2 2 0 012.83-2.83l.06.06A1.7 1.7 0 009 4.6h.09A1.7 1.7 0 0010.6 3.04V3a2 2 0 014 0v.09a1.7 1.7 0 001.03 1.56 1.7 1.7 0 001.87-.34l.06-.06a2 2 0 012.83 2.83l-.06.06A1.7 1.7 0 0019.4 9v.09a1.7 1.7 0 001.56 1.03H21a2 2 0 010 4h-.09a1.7 1.7 0 00-1.51 1.03z"/>',
        'shield' => '<path d="M12 3l8 3v6c0 5-3.4 8-8 9-4.6-1-8-4-8-9V6z"/><path d="M9 12l2 2 4-4"/>',
        'sparkles' => '<path d="M12 3l1.8 4.7L18.5 9.5l-4.7 1.8L12 16l-1.8-4.7L5.5 9.5l4.7-1.8z"/><path d="M18.5 16.5l.8 2 2 .8-2 .8-.8 2-.8-2-2-.8 2-.8z"/>',
        'star' => '<path d="M12 3.5l2.7 5.5 6 .9-4.35 4.25 1.03 6L12 17.32 6.62 20.15l1.03-6L3.3 9.9l6-.9z"/>',
        'sun' => '<circle cx="12" cy="12" r="4.2"/><path d="M12 2v2M12 20v2M4.2 4.2l1.5 1.5M18.3 18.3l1.5 1.5M2 12h2M20 12h2M4.2 19.8l1.5-1.5M18.3 5.7l1.5-1.5"/>',
        'terminal' => '<rect x="2.5" y="4" width="19" height="16" rx="2"/><path d="M7 9l3 3-3 3M13 15h4"/>',
        'trash' => '<path d="M3 6h18"/><path d="M8 6V4a1 1 0 011-1h6a1 1 0 011 1v2"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6"/>',
        'upload' => '<path d="M4 16v3a2 2 0 002 2h12a2 2 0 002-2v-3"/><path d="M8 7l4-4 4 4"/><path d="M12 3v12"/>',
        'user' => '<path d="M12 12.5a4 4 0 100-8 4 4 0 000 8z"/><path d="M4.5 20a7.5 7.5 0 0115 0"/>',
        'users' => '<path d="M9.5 12a3.75 3.75 0 100-7.5 3.75 3.75 0 000 7.5z"/><path d="M2.5 20a7 7 0 0114 0"/><path d="M16 5.2a3.75 3.75 0 010 7.1"/><path d="M18 14.4a6.5 6.5 0 013.5 5.6"/>',
        'x' => '<path d="M6 6l12 12M18 6L6 18"/>',
        'x-circle' => '<circle cx="12" cy="12" r="9"/><path d="M15 9l-6 6M9 9l6 6"/>',
    ];

    $artwork = $icons[$name] ?? null;

    // A bare number means pixels; anything else (1.5rem, 1em) is passed through.
    $resolvedSize = is_numeric($size) ? $size.'px' : $size;
@endphp

@if ($artwork === null)
    {{-- Fail loudly in development instead of rendering an invisible gap. --}}
    @if (config('app.debug'))
        <!-- Unknown icon: {{ $name }} -->
    @endif
@else
    <svg
        {{ $attributes->class('icon') }}
        @if ($resolvedSize) style="--icon-size: {{ $resolvedSize }}" @endif
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.6"
        stroke-linecap="round"
        stroke-linejoin="round"
        @if ($label) role="img" aria-label="{{ $label }}" @else aria-hidden="true" focusable="false" @endif
    >{!! $artwork !!}</svg>
@endif
