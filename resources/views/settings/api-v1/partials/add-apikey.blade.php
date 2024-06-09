<div>
    <x-primary-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'add-apikey')">
        {{ __("New API Key") }}
    </x-primary-button>

    <x-modal name="add-apikey">
        <form
            id="add-api-key-form"
            hx-post="{{ route("api-v1.store") }}"
            hx-swap="outerHTML"
            hx-select="#add-api-key-form"
            hx-ext="disable-element"
            hx-disable-element="#btn-add-apikey"
            class="p-6"
        >
            @csrf
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __("Add new API Key") }}
            </h2>

            <div class="mt-6">
                <x-input-label for="description" :value="__('Description')" />
                <x-text-input value="{{ old('description') }}" id="description" placeholder="{{ __('Description') }}" name="description" type="text" class="mt-1 w-full" />
                @error("description")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-6">
                <x-input-label for="expires_at" :value="__('Expires at (Optional)')" />
                <x-text-input value="{{ old('expires_at') }}" id="expires_at" placeholder="{{ __('Expires at') }}" name="expires_at" type="date" class="mt-1 w-full" />
                @error("expires_at")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-6">
                <x-input-label for="permission_type" value="{{ __('Permission Type') }}" />
                <x-select-input x-model="permission_type" id="permission_type" name="permission_type" class="mt-1 w-full" onchange="permissionTypeChanged(this.value)">
                    <option value="0" selected>
                        {{ __("Selected Endpoints (Recommended)") }}
                    </option>
                    <option value="1" selected>
                        {{ __("All Endpoints") }}
                    </option>
                </x-select-input>
                @error("permission_type")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-3">
                @foreach($endpoints as $endpoint)
                <div class="flex">
                    <x-checkbox :id="$endpoint['identifier']" :name="$endpoint['name']" :value="1" onchange="checkboxChanged()" />
                    <div>
                        <x-input-label for="{{ $endpoint['name'] }}" value="{{ $endpoint['label'] }}" />
                    </div>
                </div>
                @endforeach
                @error("permissions")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">
                    {{ __("Cancel") }}
                </x-secondary-button>

                <x-primary-button id="btn-add-apikey" class="ml-3">
                    {{ __("Add") }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</div>

<script>
    function permissionTypeChanged(value) {
        const checkboxes = document.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach((checkbox) => {
            checkbox.checked = value === "1";
        });
    }

    // if all checkboxes are checked, then select the "All Endpoints" option
    // and if all endpoints is selected and one checkbox is unchecked, then select the "Selected Endpoints" option
    function checkboxChanged() {
        const checkboxes = document.querySelectorAll('input[type="checkbox"]');
        const select = document.getElementById('permission_type');
        const allChecked = Array.from(checkboxes).every((checkbox) => checkbox.checked);
        select.value = allChecked ? "1" : "0";
    }
</script>
