<x-profile-layout>
    <x-slot name="pageTitle">{{ __("Profile") }}</x-slot>

    @include("settings.profile.partials.update-profile-information")

    @include("settings.profile.partials.update-password")

    @include("settings.profile.partials.two-factor-authentication")
</x-profile-layout>
