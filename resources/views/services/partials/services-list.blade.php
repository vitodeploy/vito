<div>
    <x-card-header>
        <x-slot name="title">Installed Services</x-slot>
        <x-slot name="description">All services that we installed on your server are here</x-slot>
        <x-slot name="aside"></x-slot>
    </x-card-header>

    <x-live id="live-services">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($services as $service)
                <div
                    class="relative flex h-auto flex-col items-center justify-between space-y-3 rounded-b-md rounded-t-md border border-gray-200 bg-white text-center dark:border-gray-700 dark:bg-gray-800"
                >
                    <div class="absolute right-3 top-3">
                        @include("services.partials.status", ["status" => $service->status])
                    </div>
                    <div class="space-y-3 p-5">
                        <div class="mt-5 flex items-center justify-center">
                            <img
                                src="{{ asset("static/images/" . $service->name . ".svg") }}"
                                class="h-[70px] w-[70px]"
                                alt=""
                            />
                        </div>
                        <div class="flex flex-grow flex-col items-start justify-center">
                            <div class="flex items-center">
                                <div class="flex items-center text-lg">
                                    {{ $service->name }}
                                    <x-status status="disabled" class="ml-1">{{ $service->version }}</x-status>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div
                        class="flex w-full items-center justify-between rounded-b-md border-t border-t-gray-200 bg-gray-50 p-2 dark:border-t-gray-600 dark:bg-gray-700"
                    >
                        @include("services.partials.actions." . $service->name)
                    </div>
                </div>
            @endforeach
        </div>
    </x-live>
</div>
