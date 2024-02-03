<x-modal name="restore-backup">
    <form wire:submit="restore" class="p-6" x-data="{}">
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Restore Backup') }}
        </h2>

        <div class="mt-6">
            <x-input-label for="database" :value="__('Database')" />
            <x-select-input wire:model.live="restoreDatabaseId" id="restoreDatabaseId" name="restoreDatabaseId" class="mt-1 w-full">
                <option value="" selected disabled>{{ __("Select") }}</option>
                @foreach($databases as $db)
                    <option value="{{ $db->id }}" @if($restoreDatabaseId == $db->id) selected @endif>{{ $db->name }}</option>
                @endforeach
            </x-select-input>
            @error('restoreDatabaseId')
            <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>

        <div class="mt-6 flex justify-end">
            <x-secondary-button type="button" x-on:click="$dispatch('close')">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-primary-button class="ml-3" @restored.window="$dispatch('close')">
                {{ __('Restore') }}
            </x-primary-button>
        </div>
    </form>
</x-modal>
