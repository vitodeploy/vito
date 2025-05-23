@foreach($site->activeRedirects as $redirect)
        redir {{ $redirect->from }} {{ $redirect->to }} {{ $redirect->mode }}
@endforeach
