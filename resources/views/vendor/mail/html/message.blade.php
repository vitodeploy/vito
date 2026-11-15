<x-mail::layout>
<x-slot:header>
<x-mail::header :url="config('app.url')">
{{ config('app.name') }}
</x-mail::header>
</x-slot:header>

{!! $slot !!}

@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

<x-slot:footer>
<x-mail::footer>
{{ __('Sent by') }} [{{ config('app.name') }}]({{ config('app.url') }}) &middot; {{ __('Self-hosted server management') }}
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
