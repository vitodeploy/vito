{{ $application->domain }} {{ $application->getAliasesString() }} {
@if ($application->activeSsl && $application->force_ssl)
    redir @http https://{host}{uri} permanent
@endif
@if ($application->activeSsl)
    tls {{ $application->activeSsl->certificate_path }} {{ $application->activeSsl->pk_path }}
@endif

    import access_log {{ $application->domain }}
    import compression
    import security_headers

    reverse_proxy {{ $application->type_data['scheme'] ?? 'http' }}://{{ $application->type_data['host'] ?? 'localhost' }}:{{ $application->type_data['port'] ?? 3000 }} {
@if (!empty($application->type_data['websocket']))
        header_up Upgrade {http.request.header.Upgrade}
        header_up Connection {http.request.header.Connection}
@endif
        header_up Host {host}
        header_up X-Real-IP {remote_host}
        header_up X-Forwarded-For {remote_host}
        header_up X-Forwarded-Proto {scheme}
    }
}
