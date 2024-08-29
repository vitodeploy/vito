@props([
    "id",
    "name",
    "placeholder" => "Search...",
    "items" => [],
    "maxResults" => 5,
    "value" => "",
])

<script>
    window['items_' + @js($id)] = @json($items);
</script>

<div
    x-data="{
        q: @js($value),
        items: window['items_' + @js($id)],
        resultItems: window['items_' + @js($id)],
        maxResults: @js($maxResults),
        init() {
            this.search()
        },
        search() {
            if (! this.q) {
                this.resultItems = this.items.slice(0, this.maxResults)
                return
            }
            this.resultItems = this.items
                .filter((item) => item.toLowerCase().includes(this.q.toLowerCase()))
                .slice(0, this.maxResults)
        },
    }"
>
    <input type="hidden" name="{{ $name }}" x-ref="input" x-model="q" />
    <x-dropdown width="full" :hide-if-empty="true">
        <x-slot name="trigger">
            <x-text-input
                id="$id . '-q"
                x-model="q"
                type="text"
                class="mt-1 w-full"
                :placeholder="$placeholder"
                autocomplete="off"
                x-on:input.debounce.100ms="search"
            />
        </x-slot>
        <x-slot name="content">
            <div
                id="{{ $id }}-items-list"
                x-bind:class="
                    resultItems.length > 0
                        ? 'py-1 border border-gray-200 dark:border-gray-600 rounded-md'
                        : ''
                "
            >
                <template x-for="item in resultItems">
                    <x-dropdown-link class="cursor-pointer" x-on:click="q = item">
                        <span x-text="item"></span>
                    </x-dropdown-link>
                </template>
            </div>
        </x-slot>
    </x-dropdown>
</div>
