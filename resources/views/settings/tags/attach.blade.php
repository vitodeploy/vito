<div x-data="">
    <button type="button" class="flex items-center" x-on:click="$dispatch('open-modal', 'create-tag-modal')">
        <x-heroicon name="o-plus" class="h-5 w-5 text-gray-500 dark:text-gray-400" />
        <span class="text-md">New Tag</span>
    </button>
    @push("footer")
        <x-modal name="create-tag-modal" max-width="sm">
            <form
                id="create-tag-form"
                hx-post="{{ route("tags.attach") }}"
                hx-swap="outerHTML"
                hx-select="#create-tag-form"
                class="p-6"
            >
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">New Tag</h2>

                <input type="hidden" name="taggable_type" value="{{ get_class($taggable) }}" />
                <input type="hidden" name="taggable_id" value="{{ $taggable->id }}" />

                <div class="mt-6">
                    @include("settings.tags.fields.name",["value" => old("name"),"items" => auth()->user()->currentProject->tags()->pluck("name"),])
                </div>

                <div class="mt-6 flex justify-end">
                    <x-secondary-button type="button" x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                    <x-primary-button class="ml-3" hx-disable>Save</x-primary-button>
                </div>

                @if (session()->has("status") && session()->get("status") === "tag-created")
                    <script defer>
                        window.dispatchEvent(
                            new CustomEvent('close-modal', {
                                detail: 'create-tag-modal'
                            })
                        );
                    </script>
                @endif
            </form>
        </x-modal>
    @endpush
</div>
