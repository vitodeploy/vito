<div>
    <x-card-header>
        <x-slot name="title">Supported Services</x-slot>
        <x-slot name="description">Here you can find the supported services to install</x-slot>
        <x-slot name="aside"></x-slot>
    </x-card-header>

    <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @foreach (config("core.add_on_services") as $addOn)
            <div
                class="relative flex h-auto flex-col items-center justify-between space-y-3 rounded-b-md rounded-t-md border border-gray-200 bg-white text-center dark:border-gray-700 dark:bg-gray-800"
            >
                <div class="space-y-3 p-5">
                    <div class="flex items-center justify-center">
                        <img src="{{ asset("static/images/" . $addOn . ".svg") }}" class="h-20 w-20" alt="" />
                    </div>
                    <div class="flex flex-grow flex-col items-start justify-center">
                        <div class="flex items-center">
                            <div class="flex items-center text-lg">
                                {{ $addOn }}
                            </div>
                        </div>
                    </div>
                </div>
                <div
                    class="flex w-full items-center justify-between rounded-b-md border-t border-t-gray-200 bg-gray-50 p-2 dark:border-t-gray-600 dark:bg-gray-700"
                >
                    @include("services.partials.add-on-installers." . $addOn)
                </div>
            </div>
        @endforeach
    </div>
</div>
