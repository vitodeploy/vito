<x-secondary-button
    class="ml-2 hidden h-[42px] items-center lg:flex"
    x-on:click="$dispatch('open-modal', 'metric-settings')"
>
    <x-heroicon name="o-trash" class="mr-1 h-5 w-5" />
    Data Retention
</x-secondary-button>
@push("modals")
    <x-modal name="metric-settings">
        <form
            id="metric-settings-form"
            hx-post="{{ route("servers.metrics.settings", ["server" => $server]) }}"
            hx-swap="outerHTML"
            hx-select="#metric-settings-form"
            class="p-6"
        >
            @csrf

            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Data Retention</h2>

            <div class="mt-6">
                <x-input-label for="data_retention" value="Delete metrics older than" />
                <x-select-input id="data_retention" name="data_retention" class="mt-1 w-full">
                    @foreach (config("core.metrics_data_retention") as $item)
                        <option
                            value="{{ $item }}"
                            @if($server->monitoring()->handler()->data()['data_retention'] == $item) selected @endif
                        >
                            {{ $item }} Days
                        </option>
                    @endforeach
                </x-select-input>
                @error("data_retention")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">
                    {{ __("Cancel") }}
                </x-secondary-button>

                <x-primary-button id="btn-metric-settings" hx-disable class="ml-3">
                    {{ __("Save") }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
@endpush
