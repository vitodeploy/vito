@php
    $key = str(file_get_contents(storage_path(config("core.ssh_public_key_name"))))->replace("\n", "");
@endphp

<div>
    <div>
        <div
            class="rounded-sm border-l-4 border-yellow-500 bg-yellow-100 px-4 py-3 text-yellow-700 dark:bg-yellow-500 dark:bg-opacity-10 dark:text-yellow-500"
        >
            Your server needs to have a new unused installation of supported operating systems and must have a root
            user. To get started, add our public key to /root/.ssh/authorized_keys file by running the bellow command on
            your server as root.
        </div>
    </div>
</div>
<div>
    <div class="flex items-center justify-between">
        <x-input-label for="pk">
            {{ __("Run this command on your server as root user") }}
        </x-input-label>
        <x-input-label
            class="cursor-pointer"
            x-data="{ copied: false }"
            id="public-key"
            data-clipboard="mkdir -p /root/.ssh && touch /root/.ssh/authorized_keys && echo '{{ $key }}' >> /root/.ssh/authorized_keys"
        >
            <div x-show="copied" class="flex items-center">
                {{ __("Copied") }}
            </div>
            <div
                x-show="!copied"
                x-on:click="
                    window.copyToClipboard(
                        document.getElementById('public-key').getAttribute('data-clipboard'),
                    )
                    copied = true
                    setTimeout(() => {
                        copied = false
                    }, 2000)
                "
            >
                {{ __("Copy") }}
            </div>
        </x-input-label>
    </div>
    <x-textarea id="pk" name="pk" class="mt-1" rows="5" disabled>
        mkdir -p /root/.ssh && touch /root/.ssh/authorized_keys && echo '{{ $key }}' >> /root/.ssh/authorized_keys
    </x-textarea>
</div>
