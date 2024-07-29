<div>
    <x-primary-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'add-channel')">
        {{ __("Add new Channel") }}
    </x-primary-button>

    <x-modal name="add-channel">
        @php
            $oldProvider = old("provider", request()->input("provider") ?? "");
        @endphp

        <form
            id="add-channel-form"
            hx-post="{{ route("settings.notification-channels.add") }}"
            hx-swap="outerHTML"
            hx-select="#add-channel-form"
            hx-ext="disable-element"
            hx-disable-element="#btn-add-channel"
            class="p-6"
            x-data="{ provider: '{{ $oldProvider }}' }"
        >
            @csrf
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __("Add new Channel") }}
            </h2>

            <div class="mt-6">
                <x-input-label for="provider" value="Provider" />
                <x-select-input x-model="provider" id="provider" name="provider" class="mt-1 w-full">
                    <option value="" selected disabled>
                        {{ __("Select") }}
                    </option>
                    @foreach (config("core.notification_channels_providers") as $p)
                        @if ($p !== "custom")
                            <option value="{{ $p }}" @if ($oldProvider === $p) selected @endif>
                                {{ $p }}
                            </option>
                        @endif
                    @endforeach
                </x-select-input>
                @error("provider")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-6">
                <x-input-label for="label" :value="__('Label')" />
                <x-text-input value="{{ old('label') }}" id="label" name="label" type="text" class="mt-1 w-full" />
                @error("label")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div x-show="provider === 'email'" class="mt-6">
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input value="{{ old('email') }}" id="email" name="email" type="text" class="mt-1 w-full" />
                @error("email")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div x-show="['slack', 'discord'].includes(provider)" class="mt-6">
                <x-input-label for="webhook_url" :value="__('Webhook URL')" />
                <x-text-input
                    value="{{ old('webhook_url') }}"
                    id="webhook_url"
                    name="webhook_url"
                    type="text"
                    class="mt-1 w-full"
                />
                @error("webhook_url")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div x-show="provider === 'telegram'" class="mt-6">
                <x-input-label for="bot_token" :value="__('Bot Token')" />
                <x-text-input
                    value="{{ old('bot_token') }}"
                    id="bot_token"
                    name="bot_token"
                    type="text"
                    class="mt-1 w-full"
                />
                @error("bot_token")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div x-show="provider === 'telegram'" class="mt-6">
                <x-input-label for="chat_id" :value="__('Chat ID')" />
                <x-text-input
                    value="{{ old('chat_id') }}"
                    id="chat_id"
                    name="chat_id"
                    type="text"
                    class="mt-1 w-full"
                />
                @error("chat_id")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-6">
                <x-checkbox id="global" name="global" :checked="old('global')" value="1">
                    Is Global (Accessible in all projects)
                </x-checkbox>
                @error("global")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">
                    {{ __("Cancel") }}
                </x-secondary-button>

                <x-primary-button id="btn-add-channel" class="ml-3">
                    {{ __("Add") }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
