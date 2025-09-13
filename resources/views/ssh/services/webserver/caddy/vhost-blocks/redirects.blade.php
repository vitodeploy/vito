#[redirects]
@foreach($site->activeRedirects as $redirect)
    @if ($redirect->mode === 1000)
        handle_path {{ $redirect->from }}* {
            reverse_proxy {{ $redirect->to }} {
                header_up Host {http.reverse_proxy.upstream.hostport}
                header_up X-Real-IP {http.request.remote}
                header_up X-Forwarded-For {http.request.remote}
                header_up X-Forwarded-Proto {http.request.scheme}
            }
        }
    @else
	    @exactPath{{ $redirect->id }} {
	        path == {{ $redirect->from }}
	    }
	    redir @exactPath{{ $redirect->id }} {{ $redirect->to }} {{ $redirect->mode }}
    @endif
@endforeach
#[/redirects]
