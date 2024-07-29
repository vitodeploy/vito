<x-input-label for="name" :value="__('Name')" />
<x-text-input value="{{ $value }}" id="name" name="name" type="text" class="mt-1 w-full" />
@error("name")
    <x-input-error class="mt-2" :messages="$message" />
@enderror
