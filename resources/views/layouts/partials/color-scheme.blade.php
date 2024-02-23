<div class="flex items-center text-gray-600 dark:text-gray-300" x-data="{
    theme: localStorage.theme,
    isDark() {
        if (this.theme === 'dark') {
            return true
        }
        return this.theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches;
    },
    changeTheme(theme) {
        this.theme = theme;
        localStorage.theme = theme;
        this.updateDocument();
    },
    updateDocument() {
        if (this.isDark()) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    }
}" x-init="updateDocument()">
    <div class="flex items-center">
        <div class="flex items-center justify-end">
            <x-dropdown>
                <x-slot name="trigger">
                    <button type="button" class="flex items-center">
                        <x-heroicon-o-moon x-show="isDark()" class="h-7 w-7" />
                        <x-heroicon-o-sun x-show="!isDark()" class="h-7 w-7" />
                    </button>
                </x-slot>
                <x-slot name="content">
                    <x-dropdown-link class="cursor-pointer" x-on:click="changeTheme('dark')">
                        <x-heroicon-o-moon class="h-5 w-5 mr-2" x-bind:class="theme === 'dark' ? 'text-primary-600' : ''" /> {{ __("Dark") }}
                    </x-dropdown-link>
                    <x-dropdown-link class="cursor-pointer" x-on:click="changeTheme('light')">
                        <x-heroicon-o-sun class="h-5 w-5 mr-2" x-bind:class="theme === 'light' ? 'text-primary-600' : ''" /> {{ __("Light") }}
                    </x-dropdown-link>
                    <x-dropdown-link class="cursor-pointer" x-on:click="changeTheme('system')">
                        <x-heroicon-o-computer-desktop class="h-5 w-5 mr-2" x-bind:class="theme === 'system' ? 'text-primary-600' : ''" /> {{ __("System") }}
                    </x-dropdown-link>
                </x-slot>
            </x-dropdown>
        </div>
    </div>
</div>
