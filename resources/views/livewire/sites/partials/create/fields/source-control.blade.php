<div>
    <x-input-label for="source_control" :value="__('Source Control')" />
    <div class="flex items-center mt-1">
        <x-select-input wire:model="inputs.source_control" id="source_control" name="source_control" class="mt-1 w-full">
            <option value="" selected>{{ __("Select") }}</option>
            @foreach($sourceControls as $sourceControl)
                <option value="{{ $sourceControl->id }}" @if($sourceControl->id === $inputs['source_control']) selected @endif>
                    {{ $sourceControl->profile }} ({{ $sourceControl->provider }})
                </option>
            @endforeach
        </x-select-input>
        <x-secondary-button :href="route('source-controls', ['redirect' => request()->url()])" class="flex-none ml-2">{{ __('Connect') }}</x-secondary-button>
    </div>
    @error('source_control')
    <x-input-error class="mt-2" :messages="$message" />
    @enderror
</div>
