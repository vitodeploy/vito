<x-modal
    name="execute-script"
    :show="true"
    x-on:modal-execute-script-closed.window="window.history.pushState('', '', '{{ route('scripts.index') }}');"
>
    <div
        x-data="{
            server: '',
            selectServer() {
                let url =
                    '{{ route("scripts.index", ["execute" => $script->id]) }}&server=' +
                    this.server
                window.history.pushState('', '', url)
                htmx.ajax('GET', url, {
                    target: '#select-user',
                    swap: 'outerHTML',
                    select: '#select-user',
                })
            },
        }"
    >
        <form
            id="execute-script-form"
            hx-post="{{ route("scripts.execute", ["script" => $script]) }}"
            hx-swap="outerHTML"
            hx-select="#execute-script-form"
            class="p-6"
        >
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __("Execute script") }}
            </h2>

            <div class="mt-6">
                <x-input-label for="server" :value="__('Select a server to execute')" />
                <x-select-input
                    id="server"
                    name="server"
                    x-model="server"
                    x-on:change="selectServer"
                    class="mt-1 w-full"
                >
                    <option value="" selected disabled>
                        {{ __("Select") }}
                    </option>
                    @php
                        $executeServers = auth()
                            ->user()
                            ->allServers()
                            ->get();
                    @endphp

                    @foreach ($executeServers as $executeServer)
                        <option value="{{ $executeServer->id }}">
                            {{ $executeServer->name }} [{{ $executeServer->project->name }}]
                        </option>
                    @endforeach
                </x-select-input>
                @error("server")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-6" id="select-user">
                @php
                    $s = null;
                    if (request()->has("server")) {
                        $s = auth()
                            ->user()
                            ->allServers()
                            ->findOrFail(request("server"));
                    }
                @endphp

                @include("fields.user", ["value" => old("user"), "server" => $s])
            </div>

            @if (count($script->getVariables()) > 0)
                <x-input-label class="mt-6" value="Variables" />

                <div class="mt-2 space-y-6 border-2 border-dashed border-gray-200 px-2 py-3 dark:border-gray-700">
                    @foreach ($script->getVariables() as $variable)
                        <div>
                            <x-input-label :for="'variable-' . $variable" :value="$variable" />
                            <x-text-input
                                id="variable-{{ $variable }}"
                                name="variables[{{ $variable }}]"
                                class="mt-1 w-full"
                                value="{{ old('variables.' . $variable) }}"
                            />
                        </div>
                        @error("variables." . $variable)
                            <x-input-error class="mt-2" :messages="$message" />
                        @enderror
                    @endforeach
                </div>
            @endif

            <div class="mt-6 flex justify-end">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">
                    {{ __("Cancel") }}
                </x-secondary-button>

                <x-primary-button class="ml-3" hx-disable>
                    {{ __("Execute") }}
                </x-primary-button>
            </div>
        </form>
    </div>
</x-modal>
