<x-primary-button x-on:click="$dispatch('open-modal', 'create-backup')">
    {{ __("Create Backup") }}
</x-primary-button>
<x-modal name="create-backup">
    <form
        id="create-backup-form"
        hx-post="{{ route("servers.databases.backups.store", ["server" => $server]) }}"
        hx-swap="outerHTML"
        hx-select="#create-backup-form"
        hx-ext="disable-element"
        hx-disable-element="#btn-create-backup"
        class="p-6"
    >
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __("Create Backup") }}
        </h2>

        <div class="mt-6">
            <x-input-label for="backup_database" :value="__('Database')" />
            <x-select-input id="backup_database" name="backup_database" class="mt-1 w-full">
                <option value="" selected disabled>{{ __("Select") }}</option>
                @foreach ($databases as $db)
                    <option value="{{ $db->id }}" @if($db->id == old('backup_database')) selected @endif>
                        {{ $db->name }}
                    </option>
                @endforeach
            </x-select-input>
            @error("backup_database")
                <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>

        <div class="mt-6">
            <x-input-label for="backup_storage" :value="__('Storage')" />
            <div class="mt-1 flex items-center">
                <x-select-input id="backup_storage" name="backup_storage" class="mt-1 w-full">
                    <option value="" selected disabled>{{ __("Select") }}</option>
                    @foreach (\App\Models\StorageProvider::getByProjectId(auth()->user()->current_project_id)->get() as $st)
                        <option value="{{ $st->id }}" @if(old('backup_storage') == $st->id) selected @endif>
                            {{ $st->profile }} - {{ $st->provider }}
                        </option>
                    @endforeach
                </x-select-input>
                <x-secondary-button :href="route('settings.storage-providers')" class="ml-2 flex-none">
                    Connect
                </x-secondary-button>
            </div>
            @error("backup_storage")
                <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>

        <div class="mt-6">
            <x-input-label for="backup_interval" :value="__('Interval')" />
            <x-select-input id="backup_interval" name="backup_interval" class="mt-1 w-full">
                <option value="" selected disabled>{{ __("Select") }}</option>
                <option value="0 * * * *" @if(old('backup_interval') === '0 * * * *') selected @endif>
                    {{ __("Hourly") }}
                </option>
                <option value="0 0 * * *" @if(old('backup_interval') === '0 0 * * *') selected @endif>
                    {{ __("Daily") }}
                </option>
                <option value="0 0 * * 0" @if(old('backup_interval') === '0 0 * * 0') selected @endif>
                    {{ __("Weekly") }}
                </option>
                <option value="0 0 1 * *" @if(old('backup_interval') === '0 0 1 * *') selected @endif>
                    {{ __("Monthly") }}
                </option>
                <option value="custom">{{ __("Custom") }}</option>
            </x-select-input>
            @error("backup_interval")
                <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>

        @if (old("backup_interval") === "custom")
            <div class="mt-6">
                <x-input-label for="backup_custom" :value="__('Custom interval (Cron)')" />
                <x-text-input
                    id="backup_custom"
                    name="backup_custom"
                    type="text"
                    class="mt-1 w-full"
                    placeholder="* * * * *"
                />
                @error("backup_custom")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>
        @endif

        <div class="mt-6">
            <x-input-label for="backup_keep" :value="__('Backups to Keep')" />
            <x-text-input id="backup_keep" name="backup_keep" type="text" class="mt-1 w-full" />
            @error("backup_keep")
                <x-input-error class="mt-2" :messages="$message" />
            @enderror
        </div>

        <div class="mt-6 flex justify-end">
            <x-secondary-button type="button" x-on:click="$dispatch('close')">
                {{ __("Cancel") }}
            </x-secondary-button>

            <x-primary-button id="btn-create-backup" class="ml-3">
                {{ __("Create") }}
            </x-primary-button>
        </div>
    </form>
</x-modal>
