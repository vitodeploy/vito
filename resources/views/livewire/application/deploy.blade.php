<div>
    @if ($site->deploymentScript?->content)
        <x-primary-button wire:click="deploy" wire:loading.attr="disabled">
            {{ __("Deploy") }}
        </x-primary-button>
    @endif
</div>
