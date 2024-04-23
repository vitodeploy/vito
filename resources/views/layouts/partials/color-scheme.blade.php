<div
    class="flex items-center text-gray-600 dark:text-gray-300"
    x-data="{
        theme: localStorage.theme,
        isDark() {
            if (this.theme === 'dark') {
                return true
            }
            return (
                this.theme === 'system' &&
                window.matchMedia('(prefers-color-scheme: dark)').matches
            )
        },
        changeTheme(theme) {
            this.theme = theme
            localStorage.theme = theme
            this.updateDocument()
            document.body.dispatchEvent(
                new CustomEvent('color-scheme-changed', {
                    detail: { theme: this.isDark() ? 'dark' : 'light' },
                }),
            )
        },
        updateDocument() {
            if (this.isDark()) {
                document.documentElement.classList.add('dark')
            } else {
                document.documentElement.classList.remove('dark')
            }
        },
    }"
    x-init="updateDocument()"
>
    <div class="flex items-center">
        <div class="flex items-center justify-end">
            <x-dropdown>
                <x-slot name="trigger">
                    <button
                        type="button"
                        class="flex items-center rounded-full p-1 focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-600"
                    >
                        <x-heroicon name="o-moon" x-show="isDark()" class="h-7 w-7" />
                        <x-heroicon name="o-sun" x-show="!isDark()" class="h-7 w-7" />
                    </button>
                </x-slot>
                <x-slot name="content">
                    <x-dropdown-link class="cursor-pointer" x-on:click="changeTheme('dark')">
                        <x-heroicon
                            name="o-moon"
                            class="mr-2 h-5 w-5"
                            x-bind:class="theme === 'dark' ? 'text-primary-600' : ''"
                        />
                        {{ __("Dark") }}
                    </x-dropdown-link>
                    <x-dropdown-link class="cursor-pointer" x-on:click="changeTheme('light')">
                        <x-heroicon
                            name="o-sun"
                            class="mr-2 h-5 w-5"
                            x-bind:class="theme === 'light' ? 'text-primary-600' : ''"
                        />
                        {{ __("Light") }}
                    </x-dropdown-link>
                    <x-dropdown-link class="cursor-pointer" x-on:click="changeTheme('system')">
                        <x-heroicon
                            name="o-computer-desktop"
                            class="mr-2 h-5 w-5"
                            x-bind:class="theme === 'system' ? 'text-primary-600' : ''"
                        />
                        {{ __("System") }}
                    </x-dropdown-link>
                </x-slot>
            </x-dropdown>
        </div>
    </div>
</div>
