{{ $site->domain }} {{ $site->getAliasesString() }} {
    @if ($site->activeSsl)
    tls {{ $site->activeSsl->certificate_path }} {{ $site->activeSsl->pk_path }}
    @endif
    @if ($site->activeSsl && $site->force_ssl)
    redir @http https://{host}{uri} permanent
    @endif
    import access_log {{ $site->domain }}
    import compression
    import security_headers
    @if ($site->port)
        reverse_proxy 127.0.0.1:{{ $site->port }}
    @else
        @if ($site->type()->language() === 'php')
            root * {{ $site->getWebDirectoryPath() }}
            @php
                $phpSocket = "unix//var/run/php/php{$site->php_version}-fpm.sock";
                if ($site->isIsolated()) {
                    $phpSocket = "unix//run/php/php{$site->php_version}-fpm-{$site->user}.sock";
                }
            @endphp
            try_files {path} {path}/ /index.php?{query}
            php_fastcgi {{ $phpSocket }}
            file_server
        @endif
    @endif
    @if ($site->type === \App\Enums\SiteType::LOAD_BALANCER)
        reverse_proxy {
            @if ($site->loadBalancerServers()->count() > 0)
                @foreach($site->loadBalancerServers as $server)
                    to {{ $server->ip }}:{{ $server->port }}
                @endforeach
            @else
                to 127.0.0.1
            @endif
            @switch($site->type_data['method'] ?? \App\Enums\LoadBalancerMethod::ROUND_ROBIN)
                @case(\App\Enums\LoadBalancerMethod::LEAST_CONNECTIONS)
                    lb_policy least_conn
                    @break
                @case(\App\Enums\LoadBalancerMethod::IP_HASH)
                    lb_policy ip_hash
                    @break
                @default
                    lb_policy round_robin
            @endswitch
            header_up Host {host}
            header_up X-Real-IP {remote}
        }
    @endif
    @include('ssh.services.webserver.caddy.redirects', ['site' => $site])
}
