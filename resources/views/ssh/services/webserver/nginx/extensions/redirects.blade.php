@if ($site->redirects()->count() > 0)
@foreach($site->redirects as $redirect)
location {{ $redirect->from }} {
    return {{ $redirect->mode }} {{ $redirect->to }};
}
@endforeach
@endif