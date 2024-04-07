<form
    id="install-vito-agent"
    class="w-full"
    hx-post="{{ route("servers.services.install", ["server" => $server]) }}"
    hx-swap="outerHTML"
    hx-select="#install-vito-agent"
>
    @csrf
    <input type="hidden" name="type" value="vito-agent" />
    <input type="hidden" name="version" value="latest" />
    <x-secondary-button class="!w-full" hx-disable>Install</x-secondary-button>
</form>
