<div class="mt-6">
    <x-input-label for="path" value="Absolute Path" />
    <x-text-input value="{{ old('path') }}" id="path" name="path" type="text" class="mt-1 w-full" />
    <x-input-help>The absolute path on your server that the database exists. like `/home/vito/db-backups`</x-input-help>
    <x-input-help>Make sure that the path exists and the `vito` user has permission to write to it.</x-input-help>
    @error("path")
        <x-input-error class="mt-2" :messages="$message" />
    @enderror
</div>
