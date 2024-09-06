<div class="mt-6">
    <x-input-label for="token" value="API Key" />
    <x-text-input value="{{ old('token') }}" id="token" name="token" type="text" class="mt-1 w-full" />
    @error("token")
        <x-input-error class="mt-2" :messages="$message" />
    @enderror

    <a
        class="mt-1 text-primary-500"
        href="https://dropbox.tech/developers/generate-an-access-token-for-your-own-account"
        target="_blank"
    >
        How to generate?
    </a>
</div>
