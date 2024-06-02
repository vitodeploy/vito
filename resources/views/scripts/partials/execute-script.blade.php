<x-modal name="execute-script" :show="true">
    <form
        id="execute-script-form"
        hx-post="{{ route("scripts.execute", ["script" => $script]) }}"
        hx-swap="outerHTML"
        hx-select="#execute-script-form"
        class="p-6"
    >
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __("execute script") }}
        </h2>

        <div class="mt-6">
            <x-input-label for="server" :value="__('Sever to execute')" />
            <x-select-input id="server" name="server" class="mt-1 w-full">
                <option value="" selected disabled>
                    {{ __("Select") }}
                </option>
                @php
                    /** @var \App\Models\User $user */
                    $user = auth()->user();
                @endphp

                @foreach ($user->allServers() as $server)
                    <option value="{{ $server->id }}">{{ $server->name }} [{{ $server->project->name }}]</option>
                @endforeach
            </x-select-input>
            @error("server")
                <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>

        <div class="mt-6 flex justify-end">
            <x-secondary-button type="button" x-on:click="$dispatch('close')">
                {{ __("Cancel") }}
            </x-secondary-button>

            <x-primary-button class="ml-3" hx-disable>
                {{ __("Execute") }}
            </x-primary-button>
        </div>
    </form>
</x-modal>
