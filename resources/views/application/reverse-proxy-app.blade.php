<div>
    <x-simple-card class="flex items-center justify-between">
        <span>Your reverse proxy site <strong><a target="_blank" href="{{ $site->getUrl() }}">{{ $site->getUrl() }}</a></strong> is installed and ready to use!</span>
        <x-secondary-button :href="$site->getUrl()" target="_blank">Open</x-secondary-button>
    </x-simple-card>
</div>

