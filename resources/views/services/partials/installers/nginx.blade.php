<form
    id="install-nginx"
    class="w-full"
    hx-post="{{ route("servers.services.install", ["server" => $server]) }}"
    hx-swap="outerHTML"
    hx-select="#install-nginx"
>
    @csrf
    <input type="hidden" name="name" value="nginx" />
    <input type="hidden" name="type" value="webserver" />
    <input type="hidden" name="version" value="latest" />
    <x-secondary-button class="!w-full" hx-disable>Install</x-secondary-button>
</form>
