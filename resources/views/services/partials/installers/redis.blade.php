<form
    id="install-redis"
    class="w-full"
    hx-post="{{ route("servers.services.install", ["server" => $server]) }}"
    hx-swap="outerHTML"
    hx-select="#install-redis"
>
    @csrf
    <input type="hidden" name="name" value="redis" />
    <input type="hidden" name="type" value="memory_database" />
    <input type="hidden" name="version" value="latest" />
    <x-secondary-button class="!w-full" hx-disable>Install</x-secondary-button>
</form>
