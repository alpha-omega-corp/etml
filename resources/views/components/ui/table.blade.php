@props([
    'headers' => [],     // plain strings, or ['label' => '…', 'numeric' => true]
    'hover' => true,
    'compact' => false,
    'caption' => null,
])

{{--
    <x-ui.table :headers="['Name', 'Plan', ['label' => 'MRR', 'numeric' => true]]">
        <tr>
            <td>…</td><td>…</td><td class="table__numeric">…</td>
        </tr>
    </x-ui.table>

    The slot is the <tbody> content. Pass `headers` for a simple header row, or
    use the `head` slot to build one yourself.
--}}

<div class="table-wrap">
    <table {{ $attributes->class([
        'table',
        'table--hover' => $hover,
        'table--compact' => $compact,
    ]) }}>
        @if ($caption)
            <caption class="visually-hidden">{{ $caption }}</caption>
        @endif

        @if (isset($head) || $headers !== [])
            <thead>
                @isset($head)
                    {{ $head }}
                @else
                    <tr>
                        @foreach ($headers as $header)
                            @php $header = is_array($header) ? $header : ['label' => $header]; @endphp
                            <th
                                scope="col"
                                @class(['table__numeric' => $header['numeric'] ?? false])
                            >{{ $header['label'] }}</th>
                        @endforeach
                    </tr>
                @endisset
            </thead>
        @endif

        <tbody>{{ $slot }}</tbody>

        @isset($foot)
            <tfoot>{{ $foot }}</tfoot>
        @endisset
    </table>
</div>
