<form
    id="install-supervisor"
    class="w-full"
    hx-post="{{ route("servers.services.install", ["server" => $server]) }}"
    hx-swap="outerHTML"
    hx-select="#install-supervisor"
>
    @csrf
    <input type="hidden" name="name" value="supervisor" />
    <input type="hidden" name="type" value="process_manager" />
    <input type="hidden" name="version" value="latest" />
    <x-secondary-button class="!w-full" hx-disable>Install</x-secondary-button>
</form>
