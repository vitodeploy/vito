<x-card>
    <x-slot name="title">{{ __("Update Source Control") }}</x-slot>

    <x-slot name="description">{{ __("You can change the source control provider for this site") }}</x-slot>

    <form id="update-source-control" wire:submit.prevent="update" class="space-y-6">
        <div>
            <x-input-label for="provider" :value="__('Source Control')" />
            <x-select-input wire:model.defer="source_control" id="source_control" name="source_control" class="mt-1 w-full">
                <option value="" disabled selected>{{ __("Select") }}</option>
                @foreach(\App\Models\SourceControl::all() as $sourceControl)
                    <option value="{{ $sourceControl->id }}" @if($sourceControl->id === $source_control) selected @endif>{{ $sourceControl->profile }} ({{ $sourceControl->provider }})</option>
                @endforeach
            </x-select-input>
            @error('source_control')
            <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>
    </form>

    <x-slot name="actions">
        @if (session('status') === 'source-control-updated')
            <p class="mr-2">{{ __('Saved') }}</p>
        @endif
        <x-primary-button form="update-source-control" wire:loading.attr="disabled">{{ __('Save') }}</x-primary-button>
    </x-slot>
</x-card>
