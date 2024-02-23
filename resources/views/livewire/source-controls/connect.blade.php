<div>
    <x-primary-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'connect-source-control')">
        {{ __('Connect') }}
    </x-primary-button>

    <x-modal name="connect-source-control" :show="$open">
        <form wire:submit="connect" class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __('Connect to a Source Control') }}
            </h2>

            <div class="mt-6">
                <x-input-label for="provider" value="Provider" />
                <x-select-input wire:model.live="provider" id="provider" name="provider" class="mt-1 w-full">
                    <option value="" selected disabled>{{ __("Select") }}</option>
                    @foreach(config('core.source_control_providers') as $p)
                        @if($p !== 'custom')
                            <option value="{{ $p }}" @if($provider === $p) selected @endif>{{ $p }}</option>
                        @endif
                    @endforeach
                </x-select-input>
                @error('provider')
                <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-6">
                <x-input-label for="name" value="Name" />
                <x-text-input wire:model="name" id="name" name="name" type="text" class="mt-1 w-full" />
                @error('name')
                <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            @if($provider === App\Enums\SourceControl::GITLAB)
                <div class="mt-6">
                    <x-input-label for="url" value="Url (optional)" />
                    <x-text-input wire:model="url" id="url" name="url" type="text" class="mt-1 w-full" placeholder="e.g. https://gitlab.example.com/" />
                    <x-input-help>If you run a self-managed gitlab enter the url here, leave empty to use gitlab.com</x-input-help>
                    @error('url')
                    <x-input-error class="mt-2" :messages="$message" />
                    @enderror
                </div>
            @endif

            <div class="mt-6">
                <x-input-label for="token" value="API Key" />
                <x-text-input wire:model="token" id="token" name="token" type="text" class="mt-1 w-full" />
                @error('token')
                <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-primary-button class="ml-3" @connected.window="$dispatch('close')">
                    {{ __('Connect') }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
