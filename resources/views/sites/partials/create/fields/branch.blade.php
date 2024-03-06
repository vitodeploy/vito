<div>
    <x-input-label for="branch" :value="__('Branch')" />
    <x-text-input
        value="{{ old('branch') }}"
        id="branch"
        name="branch"
        type="text"
        class="mt-1 block w-full"
        autocomplete="branch"
        placeholder="main"
    />
    @error("branch")
        <x-input-error class="mt-2" :messages="$message" />
    @enderror
</div>
