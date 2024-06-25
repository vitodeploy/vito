<div x-data="">
    <x-modal name="deployment-script" max-width="3xl">
        <form
            id="deployment-script-form"
            hx-post="{{ route("servers.sites.application.deployment-script", ["server" => $server, "site" => $site]) }}"
            hx-select="#deployment-script-form"
            hx-target="#deployment-script-form"
            hx-swap="outerHTML"
            class="p-6"
        >
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __("Deployment Script") }}
            </h2>

            <div class="mt-6">A bash script that will be executed when you run the deployment process.</div>

            <div class="mt-6">
                <x-input-label for="script" :value="__('Script')" />
                <x-textarea id="script" name="script" class="mt-1 min-h-[400px] w-full font-mono">
                    {{ old("script", $site->deploymentScript?->content) }}
                </x-textarea>
                @error("script")
                    <x-input-error class="mt-2" :messages="$message" />
                @enderror
            </div>

            <div class="mt-6">
                <div class="flex items-center">
                    <x-input-label class="mr-1" :value="__('Available Variables')" />
                    (
                    <a
                        href="https://vitodeploy.com/sites/application.html#deployment-script"
                        target="_blank"
                        class="text-primary-500"
                    >
                        {{ __("How to use?") }}
                    </a>
                    )
                </div>
                <div class="mt-1 rounded-lg bg-gray-100 p-4 dark:bg-gray-700">
                    @foreach ($site->environmentVariables() as $key => $variable)
                        {{ $key }}={{ $variable }}
                        <br />
                    @endforeach
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">
                    {{ __("Cancel") }}
                </x-secondary-button>

                <x-primary-button class="ml-3" hx-disable>
                    {{ __("Save") }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
