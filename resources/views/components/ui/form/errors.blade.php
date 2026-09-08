@props([
    'title' => 'There was a problem with your submission',
])

{{--
    A summary of every validation error on the page. Put it at the top of a long
    form so a screen reader user hears the whole list at once, alongside the
    per-field messages.
--}}

@if ($errors->any())
    <x-ui.alert variant="danger" :title="$title" {{ $attributes }}>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-ui.alert>
@endif
