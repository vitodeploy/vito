<div class="mt-6">
    <div class="grid grid-cols-2 gap-2">
        <div class="mt-6">
            <x-input-label for="host" value="Host" />
            <x-text-input value="{{ old('host') }}" id="host" name="host" type="text" class="mt-1 w-full" />
            @error("host")
                <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>
        <div class="mt-6">
            <x-input-label for="port" value="Port" />
            <x-text-input value="{{ old('port') }}" id="port" name="port" type="text" class="mt-1 w-full" />
            @error("port")
                <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>
    </div>
    <div class="mt-6">
        <x-input-label for="path" value="Path" />
        <x-text-input value="{{ old('path') }}" id="path" name="path" type="text" class="mt-1 w-full" />
        @error("path")
            <x-input-error class="mt-2" :messages="$message" />
        @enderror
    </div>
    <div class="grid grid-cols-2 gap-2">
        <div class="mt-6">
            <x-input-label for="username" value="Username" />
            <x-text-input value="{{ old('username') }}" id="username" name="username" type="text" class="mt-1 w-full" />
            @error("username")
                <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>
        <div class="mt-6">
            <x-input-label for="password" value="Password" />
            <x-text-input value="{{ old('password') }}" id="password" name="password" type="text" class="mt-1 w-full" />
            @error("password")
                <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>
    </div>
    <div class="grid grid-cols-2 gap-2">
        <div class="mt-6">
            <x-input-label for="ssl" :value="__('SSL')" />
            <x-select-input id="ssl" name="ssl" class="mt-1 w-full">
                <option value="1" @if(old('ssl')) selected @endif>
                    {{ __("Yes") }}
                </option>
                <option value="0" @if(!old('ssl')) selected @endif>
                    {{ __("No") }}
                </option>
            </x-select-input>
            @error("ssl")
                <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>
        <div class="mt-6">
            <x-input-label for="passive" :value="__('Passive')" />
            <x-select-input id="passive" name="passive" class="mt-1 w-full">
                <option value="1" @if(old('passive')) selected @endif>
                    {{ __("Yes") }}
                </option>
                <option value="0" @if(!old('passive')) selected @endif>
                    {{ __("No") }}
                </option>
            </x-select-input>
            @error("passive")
                <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>
    </div>
</div>
