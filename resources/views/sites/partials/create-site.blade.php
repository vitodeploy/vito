<div x-data="">
    <x-card id="create-site">
        <x-slot name="title">{{ __("Create new site") }}</x-slot>
        <x-slot name="description">
            {{ __("Use this form to create a new site") }}
        </x-slot>
        <form
            id="create-site-form"
            hx-post="{{ route("servers.sites.create", ["server" => $server]) }}"
            hx-trigger="submit"
            hx-select="#create-site-form"
            hx-swap="outerHTML"
            hx-ext="disable-element"
            hx-disable-element="#btn-create-site"
            class="space-y-6"
        >
            <div>
                <x-input-label>{{ __("Select site type") }}</x-input-label>
                <x-select-input
                    hx-get="{{ route('servers.sites.create', ['server' => $server]) }}"
                    hx-trigger="change"
                    hx-target="#create-site-form"
                    hx-select="#create-site-form"
                    id="type"
                    name="type"
                    class="mt-1 w-full"
                >
                    <option value="" selected disabled>
                        {{ __("Select") }}
                    </option>
                    @foreach (config("core.site_types") as $t)
                        <option value="{{ $t }}" @if($t === $type) selected @endif>
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
                    value="{{ old('domain') }}"
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

            @include("sites.partials.create.fields.aliases")

            @include("sites.partials.create." . $type)
        </form>
        <x-slot name="actions">
            <x-primary-button id="btn-create-site" hx-disable form="create-site-form">
                {{ __("Create") }}
            </x-primary-button>
        </x-slot>
    </x-card>
</div>
