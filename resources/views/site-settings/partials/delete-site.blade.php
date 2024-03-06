<div x-data="">
    <x-danger-button x-on:click="$dispatch('open-modal', 'delete-site')">
        {{ __("Delete Site") }}
    </x-danger-button>
    <x-confirmation-modal
        name="delete-site"
        :title="__('Confirm')"
        :description="__('Are you sure that you want to delete this site?')"
        method="delete"
        :action="route('servers.sites.destroy', ['server' => $server, 'site' => $site])"
    />
</div>
