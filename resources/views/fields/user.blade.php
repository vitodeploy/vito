<x-input-label for="user" :value="__('User')" />
<x-select-input id="user" name="user" class="mt-1 w-full">
    <option value="" selected disabled>
        {{ __("Select") }}
    </option>
    <option value="root" @if($value === 'root') selected @endif>root</option>
    @if (isset($server))
        <option value="{{ $server->getSshUser() }}" @if($value === $server->getSshUser()) selected @endif>
            {{ $server->getSshUser() }}
        </option>
    @endif
</x-select-input>
@error("user")
    <x-input-error class="mt-2" :messages="$message" />
@enderror
