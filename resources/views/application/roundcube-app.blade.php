<div>
    <x-simple-card class="flex items-center justify-between">
        <span>
            {{ __("Roundcube is installed and ready to use! ") }}
        </span>
        <x-secondary-button :href="$site->getUrl()" target="_blank">
            {{ __("Open Roundcube") }}
        </x-secondary-button>
    </x-simple-card>
</div>
