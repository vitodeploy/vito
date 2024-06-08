<div>
    <x-primary-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'create-cronjob')">
        {{ __("Create Cronjob") }}
    </x-primary-button>

    <x-modal name="create-cronjob">
        <form
            id="create-cronjob-form"
            hx-post="{{ route("servers.cronjobs.store", ["server" => $server]) }}"
            hx-swap="outerHTML"
            hx-select="#create-cronjob-form"
            class="p-6"
            x-data="{
                frequency: '{{ old("frequency", "* * * * *") }}',
                custom: '{{ old("custom", "") }}',
            }"
        >
            @csrf

            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __("Create Cronjob") }}
            </h2>

            <div class="mt-6">
                <x-input-label for="command" :value="__('Command')" />
                <x-text-input
                    value="{{ old('command') }}"
                    id="command"
                    name="command"
                    type="text"
                    class="mt-1 w-full"
                />
                <x-input-help class="mt-2">
                    <a href="https://vitodeploy.com/servers/cronjobs.html" target="_blank" class="text-primary-500">
                        How the command should look like?
                    </a>
                </x-input-help>
                @error("command")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-6">
                @include("fields.user", ["value" => old("user")])
            </div>

            <div class="mt-6">
                @php
                    $frequency = old("frequency", "* * * * *");
                @endphp

                <x-input-label for="frequency" :value="__('Frequency')" />
                <x-select-input id="frequency" name="frequency" class="mt-1 w-full" x-model="frequency">
                    <option value="" selected disabled>
                        {{ __("Select") }}
                    </option>
                    <option value="* * * * *" @if($frequency === '* * * * *') selected @endif>
                        {{ __("Every minute") }}
                    </option>
                    <option value="0 * * * *" @if($frequency === '0 * * * *') selected @endif>
                        {{ __("Hourly") }}
                    </option>
                    <option value="0 0 * * *" @if($frequency === '0 0 * * *') selected @endif>
                        {{ __("Daily") }}
                    </option>
                    <option value="0 0 * * 0" @if($frequency === '0 0 * * 0') selected @endif>
                        {{ __("Weekly") }}
                    </option>
                    <option value="0 0 1 * *" @if($frequency === '0 0 1 * *') selected @endif>
                        {{ __("Monthly") }}
                    </option>
                    <option value="custom">{{ __("Custom") }}</option>
                </x-select-input>
                @error("frequency")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div x-show="frequency === 'custom'" class="mt-6">
                <x-input-label for="custom" :value="__('Custom Frequency')" />
                <x-text-input
                    value="{{ old('custom') }}"
                    id="custom"
                    name="custom"
                    type="text"
                    class="mt-1 w-full"
                    placeholder="* * * * *"
                />
                @error("custom")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">
                    {{ __("Cancel") }}
                </x-secondary-button>

                <x-primary-button class="ml-3" hx-disable>
                    {{ __("Create") }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
