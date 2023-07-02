<div>
    <x-card-header>
        <x-slot name="title">{{ __("Application") }}</x-slot>
        <x-slot name="description">{{ __("Here you can manage your application") }}</x-slot>
        <x-slot name="aside">
            <div class="flex items-center">
                <div class="mr-2">
                    <livewire:application.change-branch :site="$site" />
                </div>
                <div class="mr-2">
                    <livewire:application.deployment-script :site="$site" />
                </div>
                <div>
                    <livewire:application.deploy :site="$site" />
                </div>
            </div>
        </x-slot>
    </x-card-header>

    <livewire:application.deployments-list :site="$site" />
</div>
