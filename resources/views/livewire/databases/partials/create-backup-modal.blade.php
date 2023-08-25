<x-modal name="create-backup">
    <form wire:submit.prevent="create" class="p-6" x-data="{user: false, remote: false}">
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Create Backup') }}
        </h2>

        <div class="mt-6">
            <x-input-label for="database" :value="__('Database')" />
            <x-select-input wire:model="database" id="database" name="database" class="mt-1 w-full">
                <option value="" selected disabled>{{ __("Select") }}</option>
                @foreach($databases as $db)
                    <option value="{{ $db->id }}" @if($database == $db->id) selected @endif>{{ $db->name }}</option>
                @endforeach
            </x-select-input>
            @error('database')
            <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>

        <div class="mt-6">
            <x-input-label for="storage" :value="__('Storage')" />
            <x-select-input wire:model="storage" id="storage" name="storage" class="mt-1 w-full">
                <option value="" selected disabled>{{ __("Select") }}</option>
                @foreach(auth()->user()->storageProviders as $st)
                    <option value="{{ $st->id }}" @if($storage == $st->id) selected @endif>{{ $st->profile }} - {{ $st->provider }}</option>
                @endforeach
            </x-select-input>
            @error('storage')
            <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>


        <div class="mt-6">
            <x-input-label for="interval" :value="__('Interval')" />
            <x-select-input wire:model="interval" id="interval" name="interval" class="mt-1 w-full">
                <option value="" selected disabled>{{ __("Select") }}</option>
                <option value="0 * * * *" @if($interval === '0 * * * *') selected @endif>{{ __("Hourly") }}</option>
                <option value="0 0 * * *" @if($interval === '0 0 * * *') selected @endif>{{ __("Daily") }}</option>
                <option value="0 0 * * 0" @if($interval === '0 0 * * 0') selected @endif>{{ __("Weekly") }}</option>
                <option value="0 0 1 * *" @if($interval === '0 0 1 * *') selected @endif>{{ __("Monthly") }}</option>
                <option value="custom">{{ __("Custom") }}</option>
            </x-select-input>
            @error('interval')
            <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>


        @if($interval === 'custom')
            <div class="mt-6">
                <x-input-label for="custom" :value="__('Custom interval (Cron)')" />
                <x-text-input wire:model.defer="custom" id="custom" name="custom" type="text" class="mt-1 w-full" placeholder="* * * * *" />
                @error('custom')
                <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>
        @endif

        <div class="mt-6">
            <x-input-label for="keep" :value="__('Backups to Keep')" />
            <x-text-input wire:model.defer="keep" id="keep" name="keep" type="text" class="mt-1 w-full" />
            @error('keep')
            <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>

        <div class="mt-6 flex justify-end">
            <x-secondary-button type="button" x-on:click="$dispatch('close')">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-primary-button class="ml-3" @backup-created.window="$dispatch('close')">
                {{ __('Create') }}
            </x-primary-button>
        </div>
    </form>
</x-modal>
