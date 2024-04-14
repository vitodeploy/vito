<form
    id="install-ufw"
    class="w-full"
    hx-post="{{ route("servers.services.install", ["server" => $server]) }}"
    hx-swap="outerHTML"
    hx-select="#install-ufw"
>
    @csrf
    <input type="hidden" name="name" value="ufw" />
    <input type="hidden" name="type" value="firewall" />
    <input type="hidden" name="version" value="latest" />
    <x-secondary-button class="!w-full" hx-disable>Install</x-secondary-button>
</form>
