<x-settings-layout>
    <x-slot name="pageTitle">{{ __("Profile") }}</x-slot>

    @include("profile.partials.update-profile-information")

    @include("profile.partials.update-password")

    @include("profile.partials.two-factor-authentication")
</x-settings-layout>
