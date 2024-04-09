<x-simple-card>
    <div class="mb-5 flex items-center justify-between">
        <x-title>{{ __("Last Events") }}</x-title>
    </div>
    <x-table>
        <x-thead>
            <tr>
                <x-th>{{ __("Type") }}</x-th>
                <x-th>{{ __("Location") }}</x-th>
                <x-th>{{ __("Datetime") }}</x-th>
            </tr>
        </x-thead>
        <x-tbody>
            @php
                $events = $monitor
                    ->events()
                    ->latest()
                    ->limit(7)
                    ->get()
            @endphp

            @foreach ($events as $event)
                <x-tr>
                    <x-td class="uppercase">{{ $event->type }}</x-td>
                    <x-td class="uppercase">{{ $record->location }}</x-td>
                    <x-td>
                        <x-datetime :value="$event->created_at" />
                    </x-td>
                </x-tr>
            @endforeach
        </x-tbody>
    </x-table>
</x-simple-card>
