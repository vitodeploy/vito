<div>
    <x-secondary-button
        id="btn-reboot"
        hx-post="{{ route('servers.settings.reboot', ['server' => $server]) }}"
        hx-swap="none"
        hx-ext="disable-element"
        hx-disable-element="#btn-reboot"
    >
        {{ __("Reboot") }}
    </x-secondary-button>
</div>
