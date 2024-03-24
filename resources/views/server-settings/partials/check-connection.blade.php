<div>
    <x-primary-button
        id="btn-check-connection"
        hx-post="{{ route('servers.settings.check-connection', ['server' => $server]) }}"
        hx-swap="none"
        hx-ext="disable-element"
        hx-disable-element="#btn-check-connection"
    >
        {{ __("Check Connection") }}
    </x-primary-button>
</div>
