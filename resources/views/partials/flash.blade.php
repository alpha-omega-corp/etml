@php
    /**
     * Renders session flash messages as toasts. Set them from a controller with
     * `->with('success', 'Saved.')`; the keys below map to toast variants.
     */
    $variants = ['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'status' => 'info'];
    $messages = collect($variants)
        ->filter(fn ($variant, $key) => session()->has($key))
        ->map(fn ($variant, $key) => ['variant' => $variant, 'message' => session($key)]);
@endphp

@if ($messages->isNotEmpty())
    <div class="toast-region" aria-live="polite" aria-atomic="false">
        @foreach ($messages as $item)
            <x-ui.toast :variant="$item['variant']">{{ $item['message'] }}</x-ui.toast>
        @endforeach
    </div>
@endif
