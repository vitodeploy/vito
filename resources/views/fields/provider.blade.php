<x-filament-forms::field-wrapper.label>
    {{ $getLabel() }}
</x-filament-forms::field-wrapper.label>
<div x-data="{ state: $wire.$entangle('{{ $getStatePath() }}') }" class="mt-1 grid grid-cols-6 gap-2">
    @foreach (config("core.server_providers") as $p)
        <div
            class="flex w-full cursor-pointer items-center justify-center rounded-md border-2 bg-transparent px-3 pb-2 pt-3"
            @click="state = '{{ $p }}'; $wire.set('{{ $getStatePath() }}', state)"
            :class="{ 'border-primary-600': state === '{{ $p }}', 'border-primary-200 dark:border-primary-600 dark:border-opacity-20': state !== '{{ $p }}' }"
        >
            <div class="flex w-full flex-col items-center justify-center text-center">
                <img src="{{ asset("static/images/" . $p . ".svg") }}" class="h-7" alt="Server" />
                <span class="md:text-normal mt-2 hidden text-sm md:block">
                    {{ $p }}
                </span>
            </div>
        </div>
    @endforeach
</div>
