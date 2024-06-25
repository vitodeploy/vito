<div>
    <x-input-label for="source_control" :value="__('Source Control')" />
    <div class="mt-1 flex items-center">
        <x-select-input id="source_control" name="source_control" class="mt-1 w-full">
            <option value="" selected>{{ __("Select") }}</option>
            @foreach (\App\Models\SourceControl::getByProjectId(auth()->user()->current_project_id)->get() as $sourceControl)
                <option
                    value="{{ $sourceControl->id }}"
                    @if($sourceControl->id == old('source_control', isset($site) ? $site->source_control_id : null)) selected @endif
                >
                    {{ $sourceControl->profile }}
                    ({{ $sourceControl->provider }})
                </option>
            @endforeach
        </x-select-input>
        <x-secondary-button
            :href="route('settings.source-controls', ['redirect' => request()->url()])"
            class="ml-2 flex-none"
        >
            {{ __("Connect") }}
        </x-secondary-button>
    </div>
    @error("source_control")
        <x-input-error class="mt-2" :messages="$message" />
    @enderror
</div>
