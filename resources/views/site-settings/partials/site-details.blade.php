<x-card id="server-details">
    <x-slot name="title">{{ __("Details") }}</x-slot>
    <x-slot name="description">
        {{ __("More details about your site") }}
    </x-slot>
    <div class="flex items-center justify-between">
        <div>{{ __("Created At") }}</div>
        <div>
            <x-datetime :value="$site->created_at" />
        </div>
    </div>
    <div>
        <div class="py-5">
            <div class="border-t border-gray-200 dark:border-gray-700"></div>
        </div>
    </div>
    <div class="flex items-center justify-between">
        <div>{{ __("Type") }}</div>
        <div class="capitalize">{{ $site->type }}</div>
    </div>
    <div>
        <div class="py-5">
            <div class="border-t border-gray-200 dark:border-gray-700"></div>
        </div>
    </div>
    <div class="flex items-center justify-between">
        <div>{{ __("Site ID") }}</div>
        <div class="flex items-center">
            <span class="rounded-md bg-gray-100 p-1 dark:bg-gray-700">
                {{ $site->id }}
            </span>
        </div>
    </div>
    <div>
        <div class="py-5">
            <div class="border-t border-gray-200 dark:border-gray-700"></div>
        </div>
    </div>
    <div class="flex items-center justify-between">
        <div>{{ __("Status") }}</div>
        <div class="flex items-center">
            @include("sites.partials.site-status")
        </div>
    </div>
    <div>
        <div class="py-5">
            <div class="border-t border-gray-200 dark:border-gray-700"></div>
        </div>
    </div>
    <div class="flex items-center justify-between">
        <div>{{ __("Tags") }}</div>
        <div>
            @include("settings.tags.tags", ["taggable" => $site, "edit" => true])
        </div>
    </div>
</x-card>
