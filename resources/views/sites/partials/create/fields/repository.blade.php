<div>
    <x-input-label for="repository" :value="__('Repository')" />
    <x-text-input
        value="{{ old('repository') }}"
        id="repository"
        name="repository"
        type="text"
        class="mt-1 block w-full"
        autocomplete="repository"
        placeholder="organization/repository"
    />
    @error("repository")
        <x-input-error class="mt-2" :messages="$message" />
    @enderror
</div>
