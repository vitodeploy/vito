<div x-data="">
    <x-card>
        <x-slot name="title">{{ __("Create new site") }}</x-slot>
        <x-slot name="description">
            {{ __("Use this form to create a new site") }}
        </x-slot>
        <form id="create-site" wire:submit="create" class="space-y-6">
            <div>
                <x-input-label>{{ __("Select site type") }}</x-input-label>
                <x-select-input
                    wire:model.live="inputs.type"
                    id="type"
                    name="type"
                    class="mt-1 w-full"
                >
                    <option value="" selected disabled>
                        {{ __("Select") }}
                    </option>
                    @foreach (config("core.site_types") as $t)
                        <option
                            value="{{ $t }}"
                            @if($t === $inputs['type']) selected @endif
                        >
                            {{ $t }}
                        </option>
                    @endforeach
                </x-select-input>
                @error("type")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div>
                <x-input-label for="domain" :value="__('Domain')" />
                <x-text-input
                    wire:model="inputs.domain"
                    id="domain"
                    name="domain"
                    type="text"
                    class="mt-1 block w-full"
                    autocomplete="domain"
                    placeholder="example.com"
                />
                @error("domain")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div>
                <x-input-label for="alias" :value="__('Alias')" />
                <x-text-input
                    wire:model="inputs.alias"
                    id="alias"
                    name="alias"
                    type="text"
                    class="mt-1 block w-full"
                    autocomplete="alias"
                    placeholder="www.example.com"
                />
                @error("alias")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            @if (isset($inputs["type"]) && $inputs["type"])
                @include("livewire.sites.partials.create." . $inputs["type"])
            @endif
        </form>
        <x-slot name="actions">
            <x-primary-button form="create-site" wire:loading.attr="disabled">
                {{ __("Create") }}
            </x-primary-button>
        </x-slot>
    </x-card>
</div>
