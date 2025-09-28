@component('mail::message')
    {{ __('You have been invited to join the :project project!', ['project' => $project->name]) }}

    @component('mail::button', ['url' => $acceptUrl])
        {{ __('Accept Invitation') }}
    @endcomponent

    {{ __('If you did not expect to receive an invitation to this project, you may discard this email.') }}
@endcomponent
