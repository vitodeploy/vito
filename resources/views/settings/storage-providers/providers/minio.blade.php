<div class="mt-6">
    <div class="mt-6">
        <x-input-label for="api_url" value="API Url" />
        <x-text-input value="{{ old('api_url') }}" id="api_url" name="api_url" type="text" class="mt-1 w-full" />
        @error("api_url")
        <x-input-error class="mt-2" :messages="$message" />
        @enderror
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
            <x-input-label for="key" value="Key" />
            <x-text-input value="{{ old('key') }}" id="key" name="key" type="text" class="mt-1 w-full" />
            @error("key")
                <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>
        <div class="mt-6">
            <x-input-label for="secret" value="Secret" />
            <x-text-input value="{{ old('secret') }}" id="secret" name="secret" type="text" class="mt-1 w-full" />
            @error("secret")
                <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>

        <a
            class="mt-1 text-primary-500"
            href="https://min.io/docs/minio/linux/administration/identity-access-management.html"
            target="_blank"
        >
            How to generate?
        </a>
    </div>
    <div class="grid grid-cols-2 gap-2">
        <div class="mt-6">
            <x-input-label for="region" value="Region" />
            <x-text-input value="{{ old('region') }}" id="region" name="region" type="text" class="mt-1 w-full" />
            @error("region")
                <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>
        <div class="mt-6">
            <x-input-label for="bucket" value="Bucket" />
            <x-text-input value="{{ old('bucket') }}" id="bucket" name="bucket" type="text" class="mt-1 w-full" />
            @error("bucket")
                <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>
    </div>
</div>
