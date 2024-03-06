<div>
    <x-input-label for="php_version" :value="__('PHP Version')" />
    <x-select-input id="php_version" name="php_version" class="mt-1 w-full">
        <option value="" selected>{{ __("Select") }}</option>
        @foreach ($server->installedPHPVersions() as $version)
            <option value="{{ $version }}" @if($version == old('php_version')) selected @endif>
                PHP {{ $version }}
            </option>
        @endforeach
    </x-select-input>
    @error("php_version")
        <x-input-error class="mt-2" :messages="$message" />
    @enderror
</div>
