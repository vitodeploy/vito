<x-card>
    <x-slot name="title">{{ __("Installing") }}</x-slot>
    <x-slot name="description">{{ __("The server is being installed") }}</x-slot>
    <div class="relative flex h-6 overflow-hidden rounded bg-primary-200 text-xs dark:bg-primary-500 dark:bg-opacity-10">
        <div
            style="width:{{ $server->progress }}%;"
            class="flex flex-col justify-center whitespace-nowrap bg-primary-500 text-center text-white shadow-none"
        ></div>
        <span class="absolute left-0 right-0 top-1 font-semibold text-center {{ $server->progress >= 40 ? 'text-white' : 'text-black dark:text-white' }}">
            {{ $server->progress }}%
        </span>
    </div>
    <div class="text-center mt-3">
        <span class="font-bold">{{ $server->progress_step }}</span>
    </div>
</x-card>
