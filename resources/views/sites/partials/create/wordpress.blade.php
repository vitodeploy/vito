@include("sites.partials.create.fields.php-version")

<div>
    <x-input-label for="title" :value="__('Title')" />
    <x-text-input
        value="{{ old('title') }}"
        id="title"
        name="title"
        type="text"
        class="mt-1 block w-full"
        autocomplete="branch"
    />
    @error("title")
        <x-input-error class="mt-2" :messages="$message" />
    @enderror
</div>

<div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
    <div>
        <x-input-label for="email" :value="__('WP Admin Email')" />
        <x-text-input
            value="{{ old('email') }}"
            id="email"
            name="email"
            type="email"
            class="mt-1 block w-full"
            autocomplete="email"
        />
        @error("email")
            <x-input-error class="mt-2" :messages="$message" />
        @enderror
    </div>

    <div>
        <x-input-label for="username" :value="__('WP Admin Username')" />
        <x-text-input
            value="{{ old('username') }}"
            id="username"
            name="username"
            type="text"
            class="mt-1 block w-full"
            autocomplete="username"
        />
        @error("username")
            <x-input-error class="mt-2" :messages="$message" />
        @enderror
    </div>

    <div>
        <x-input-label for="password" :value="__('WP Admin Password')" />
        <x-text-input
            value="{{ old('password') }}"
            id="password"
            name="password"
            type="text"
            class="mt-1 block w-full"
        />
        @error("title")
            <x-input-error class="mt-2" :messages="$message" />
        @enderror
    </div>
</div>

<div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
    <div>
        <x-input-label for="database" :value="__('Database Name')" />
        <x-text-input
            value="{{ old('database') }}"
            id="database"
            name="database"
            type="text"
            class="mt-1 block w-full"
            autocomplete="database"
        />
        <x-input-help>
            {{ __("It will create a database with this name") }}
        </x-input-help>
        @error("database")
            <x-input-error class="mt-2" :messages="$message" />
        @enderror
    </div>

    <div>
        <x-input-label for="database" :value="__('Database User')" />
        <x-text-input
            value="{{ old('database_user') }}"
            id="database_user"
            name="database_user"
            type="text"
            class="mt-1 block w-full"
            autocomplete="database_user"
        />
        <x-input-help>
            {{ __("It will create a database user with this username") }}
        </x-input-help>
        @error("database_user")
            <x-input-error class="mt-2" :messages="$message" />
        @enderror
    </div>

    <div>
        <x-input-label for="password" :value="__('Database Password')" />
        <x-text-input
            value="{{ old('database_password') }}"
            id="database_password"
            name="database_password"
            type="text"
            class="mt-1 block w-full"
        />
        @error("database_password")
            <x-input-error class="mt-2" :messages="$message" />
        @enderror
    </div>
</div>
