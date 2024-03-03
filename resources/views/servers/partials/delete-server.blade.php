<div x-data="">
    <x-danger-button x-on:click="$dispatch('open-modal', 'delete-server')">
        {{ __("Delete Server") }}
    </x-danger-button>
    <x-confirmation-modal
        name="delete-server"
        action="{{ route('servers.delete', $server) }}"
        :title="__('Confirm')"
        :description="__('Are you sure that you want to delete this server?')"
        method="delete"
    />
</div>
