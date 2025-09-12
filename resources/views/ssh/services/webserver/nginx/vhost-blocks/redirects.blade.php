#[redirects]
@foreach($site->activeRedirects as $redirect)
    @if ($redirect->mode === 1000)
        location {{ $redirect->from }} {
            proxy_pass {{ $redirect->to }};
            proxy_set_header Host {{ parse_url($redirect->to, PHP_URL_HOST) }};
            proxy_set_header X-Real-IP $remote_addr;
            proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
            proxy_set_header X-Forwarded-Proto $scheme;
            proxy_ssl_server_name on;
        }
    @else
        location = {{ $redirect->from }} {
            return {{ $redirect->mode }} {{ $redirect->to }};
        }
    @endif
@endforeach
#[/redirects]
