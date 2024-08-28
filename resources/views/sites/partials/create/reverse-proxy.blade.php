<div>
    <x-input-label for="port" :value="__('Port')" />
    <x-text-input
        value="{{ old('port') }}"
        id="port"
        name="port"
        type="text"
        class="mt-1 block w-full"
        placeholder="8080"
    />
    @error("port")
    <x-input-error class="mt-2" :messages="$message" />
    @enderror
</div>
