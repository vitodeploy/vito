@component('mail::message')
# {{ __('You\'ve been invited') }}

{{ __('You have been invited to collaborate on the :project project in Vito.', ['project' => $project->name]) }}

{{ __('Click the button below to accept the invitation and get started.') }}

@component('mail::button', ['url' => $acceptUrl, 'color' => 'primary'])
{{ __('Accept invitation') }}
@endcomponent

@slot('subcopy')
{{ __('If you did not expect to receive an invitation to this project, you may safely discard this email.') }}
@endslot
@endcomponent
