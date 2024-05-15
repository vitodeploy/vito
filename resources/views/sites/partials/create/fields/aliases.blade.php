<script>
    let aliases = @json($aliases ?? []);
</script>
<div
    x-data="{
        aliasInput: '',
        aliases: aliases,
        removeAlias(alias) {
            this.aliases = this.aliases.filter((a) => a !== alias)
        },
        addAlias() {
            if (! this.aliasInput) {
                return
            }

            if (this.aliases.includes(this.aliasInput)) {
                return
            }

            this.aliases.push(this.aliasInput)
            this.aliasInput = ''
        },
    }"
>
    <x-input-label for="alias" :value="__('Alias')" />
    <div class="flex items-center">
        <x-text-input
            value="{{ old('alias') }}"
            id="alias"
            x-model="aliasInput"
            name="alias"
            type="text"
            class="mt-1 block w-full"
            autocomplete="alias"
            placeholder="www.example.com"
        />
        <x-secondary-button type="button" class="ml-2 flex-none" x-on:click="addAlias()">
            {{ __("Add") }}
        </x-secondary-button>
    </div>
    <div class="mt-1">
        <template x-for="alias in aliases">
            <div class="mr-1 inline-flex">
                <x-status status="info" class="flex items-center lowercase">
                    <span x-text="alias"></span>
                    <x-heroicon name="o-x-mark" class="ml-1 h-4 w-4 cursor-pointer" x-on:click="removeAlias(alias)" />
                    <input type="hidden" name="aliases[]" x-bind:value="alias" />
                </x-status>
            </div>
        </template>
    </div>
    @error("aliases")
        <x-input-error class="mt-2" :messages="$message" />
    @enderror
</div>
