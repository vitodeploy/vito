@php
    use App\Enums\Database;
    use App\Enums\Webserver;
    use App\Enums\ServerType;
@endphp

<x-container x-data="{type: '{{ old('type', ServerType::REGULAR) }}'}">
    <x-card>
        <x-slot name="title">{{ __("Create new Server") }}</x-slot>
        <x-slot name="description">
            {{ __("Use this form to create a new server") }}
        </x-slot>
        @php
            $oldProvider = old("provider", request()->input("provider") ?? "");
        @endphp

        <form
            id="create-server-form"
            hx-post="{{ route("servers.create") }}"
            hx-swap="outerHTML"
            hx-trigger="submit"
            hx-select="#create-server-form"
            hx-ext="disable-element"
            hx-disable-element="#btn-create-server"
            class="mt-6 space-y-6"
        >
            @csrf
            <div>
                <x-input-label>
                    {{ __("Select a server provider") }}
                </x-input-label>
                <div class="mt-1 grid grid-cols-6 gap-2">
                    @foreach (config("core.server_providers") as $p)
                        <x-server-provider-item
                            hx-get="{{ route('servers.create', ['provider' => $p]) }}"
                            hx-select="#create-server-form"
                            hx-target="#create-server-form"
                            :active="old('provider', $provider) == $p"
                        >
                            <div class="flex w-full flex-col items-center justify-center text-center">
                                <img src="{{ asset("static/images/" . $p . ".svg") }}" class="h-7" alt="Server" />
                                <span class="md:text-normal mt-2 hidden text-sm md:block">
                                    {{ $p }}
                                </span>
                            </div>
                        </x-server-provider-item>
                    @endforeach

                    <input type="hidden" name="provider" value="{{ old("provider", $provider) }}" />
                </div>
                @error("provider")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            @if ($provider == "custom")
                @include("servers.partials.public-key")
            @else
                <div>
                    <x-input-label for="server_provider" value="Provider Profile" />
                    <div class="mt-1 flex items-center">
                        <x-select-input id="server_provider" name="server_provider" class="w-full">
                            <option value="" disabled selected>
                                {{ __("Select") }}
                            </option>
                            @foreach ($serverProviders as $sp)
                                <option value="{{ $sp->id }}" @if($sp->id == old('server_provider')) selected @endif>
                                    {{ $sp->profile }}
                                </option>
                            @endforeach
                        </x-select-input>
                        <x-secondary-button
                            :href="route('settings.server-providers', ['provider' => $provider])"
                            class="ml-2 flex-none"
                        >
                            {{ __("Connect") }}
                        </x-secondary-button>
                    </div>
                    @error("server_provider")
                        <x-input-error class="mt-2" :messages="$message" />
                    @enderror

                    @if (count($serverProviders) == 0)
                        <x-input-error class="mt-2" :messages="__('You have not connected to any providers!')" />
                    @endif
                </div>
            @endif

            <div>
                <x-input-label for="name" :value="__('Name')" />
                <x-text-input
                    value="{{ old('name') }}"
                    id="name"
                    name="name"
                    type="text"
                    class="mt-1 block w-full"
                    autocomplete="name"
                />
                @error("name")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            @if ($provider !== "custom")
                <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                    <div>
                        <x-input-label for="plan" value="Plan" />
                        <x-select-input id="plan" name="plan" class="mt-1 w-full">
                            <option value="" disabled selected>
                                {{ __("Select") }}
                            </option>
                            @foreach (config("serverproviders")[$provider]["plans"] as $value)
                                <option
                                    value="{{ $value["value"] }}"
                                    @if($value['value'] == old('plan')) selected @endif
                                >
                                    {{ $value["title"] }}
                                </option>
                            @endforeach
                        </x-select-input>
                        @error("plan")
                            <x-input-error class="mt-2" :messages="$message" />
                        @enderror
                    </div>
                    <div>
                        <x-input-label for="region" value="Region" />
                        <x-select-input id="region" name="region" class="mt-1 w-full">
                            <option value="" disabled selected>
                                {{ __("Select") }}
                            </option>
                            @foreach (config("serverproviders")[$provider]["regions"] as $key => $value)
                                <option
                                    value="{{ $value["value"] }}"
                                    @if($value['value'] == old('region')) selected @endif
                                >
                                    {{ $value["title"] }}
                                </option>
                            @endforeach
                        </x-select-input>
                        @error("region")
                            <x-input-error class="mt-2" :messages="$message" />
                        @enderror
                    </div>
                </div>
            @endif

            @if ($provider == "custom")
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <x-input-label for="ip" :value="__('SSH IP Address')" />
                        <x-text-input
                            value="{{ old('ip') }}"
                            id="ip"
                            name="ip"
                            type="text"
                            class="mt-1 block w-full"
                            autocomplete="ip"
                        />
                        @error("ip")
                            <x-input-error class="mt-2" :messages="$message" />
                        @enderror
                    </div>
                    <div>
                        <x-input-label for="port" :value="__('SSH Port')" />
                        <x-text-input
                            value="{{ old('port') }}"
                            id="port"
                            name="port"
                            type="text"
                            class="mt-1 block w-full"
                            autocomplete="port"
                        />
                        @error("port")
                            <x-input-error class="mt-2" :messages="$message" />
                        @enderror
                    </div>
                </div>
            @endif

            <div>
                <x-input-label for="os" value="Operating System" />
                <x-select-input id="os" name="os" class="mt-1 w-full">
                    @foreach (config("core.operating_systems") as $operatingSystem)
                        <option
                            value="{{ $operatingSystem }}"
                            @if($operatingSystem == old('os', \App\Enums\OperatingSystem::UBUNTU24)) selected @endif
                        >
                            {{ str($operatingSystem)->replace("_", " ")->ucfirst() }}
                            LTS
                        </option>
                    @endforeach
                </x-select-input>
                @error("os")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div>
                <x-input-label for="type" value="Server Type" />
                <x-select-input x-model="type" id="type" name="type" class="mt-1 w-full">
                    @foreach (config("core.server_types") as $serverType)
                        <option value="{{ $serverType }}" @if($serverType == old('type')) selected @endif>
                            {{ ucfirst($serverType) }}
                        </option>
                    @endforeach
                </x-select-input>
                @error("type")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="grid grid-cols-1 gap-3 lg:grid-cols-3">
                <div x-show="['{{ ServerType::REGULAR }}'].includes(type)">
                    <x-input-label for="webserver" value="Webserver" />
                    <x-select-input id="webserver" name="webserver" class="mt-1 w-full">
                        @foreach (config("core.webservers") as $ws)
                            <option value="{{ $ws }}" @if($ws == old('webserver', Webserver::NGINX)) selected @endif>
                                {{ $ws }}
                            </option>
                        @endforeach
                    </x-select-input>
                    @error("webserver")
                        <x-input-error class="mt-2" :messages="$message" />
                    @enderror
                </div>
                <div x-show="['{{ ServerType::REGULAR }}', '{{ ServerType::DATABASE }}'].includes(type)">
                    <x-input-label for="database" value="Database" />
                    <x-select-input id="database" name="database" class="mt-1 w-full">
                        @foreach (config("core.databases") as $db)
                            <option value="{{ $db }}" @if($db == old('database', Database::NONE)) selected @endif>
                                {{ config("core.databases_name")[$db] }} {{ config("core.databases_version")[$db] }}
                            </option>
                        @endforeach
                    </x-select-input>
                    @error("database")
                        <x-input-error class="mt-2" :messages="$message" />
                    @enderror
                </div>
                <div x-show="['{{ ServerType::REGULAR }}'].includes(type)">
                    <x-input-label for="php" value="PHP" />
                    <x-select-input id="php" name="php" class="mt-1 w-full">
                        <option value="none" @if('none' == old('php', '8.2')) selected @endif>none</option>
                        @foreach (config("core.php_versions") as $p)
                            <option value="{{ $p }}" @if($p == old('php', '8.2')) selected @endif>
                                {{ $p }}
                            </option>
                        @endforeach
                    </x-select-input>
                    @error("php")
                        <x-input-error class="mt-2" :messages="$message" />
                    @enderror
                </div>
            </div>
        </form>
        <x-slot name="actions">
            <x-primary-button id="btn-create-server" form="create-server-form">
                {{ __("Create") }}
            </x-primary-button>
        </x-slot>
    </x-card>
</x-container>
