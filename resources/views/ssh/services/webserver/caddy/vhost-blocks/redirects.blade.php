#[redirects]
@foreach($site->activeRedirects as $redirect)
    location = {{ $redirect->from }} {
        return {{ $redirect->mode }} {{ $redirect->to }};
    }
@endforeach
#[/redirects]
