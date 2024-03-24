<x-modal name="delete-project" :show="$errors->isNotEmpty()">
    <form id="delete-project-form" method="post" x-bind:action="deleteAction" class="p-6">
        @csrf
        @method("delete")

        <h2 class="text-lg font-medium">
            Deleting a project will delete all of its servers, sites, etc. Are you sure you want to delete this project?
        </h2>

        <div class="mt-6 flex justify-end">
            <x-secondary-button type="button" x-on:click="$dispatch('close')">
                {{ __("Cancel") }}
            </x-secondary-button>

            <x-danger-button class="ml-3">
                {{ __("Delete") }}
            </x-danger-button>
        </div>
    </form>
</x-modal>
