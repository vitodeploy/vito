<x-icon-button
    data-tooltip="Uninstall Service"
    class="cursor-pointer"
    x-on:click="$dispatch('open-modal', 'uninstall-{{ $service->id }}')"
>
    <x-heroicon name="o-trash" class="h-5 w-5" />
</x-icon-button>
@push("modals")
    <x-modal name="uninstall-{{ $service->id }}">
        <form
            id="uninstall-{{ $service->id }}-form"
            hx-post="{{ route("servers.services.uninstall", ["server" => $server, "service" => $service]) }}"
            hx-target="#uninstall-{{ $service->id }}-form"
            hx-select="#uninstall-{{ $service->id }}-form"
            hx-swap="outerHTML"
            class="p-6"
        >
            @csrf
            @method("delete")
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Confirm</h2>
            <p>Are you sure that you want to uninstall this service?</p>

            @error("service")
                <x-input-error class="mt-2" :messages="$message" />
            @enderror

            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                <x-danger-button class="ml-3" hx-disable>Confirm</x-danger-button>
            </div>
        </form>
    </x-modal>
@endpush
