@if ($site->activeSsl && $site->force_ssl)
    server {
        listen 80;
        server_name {{ $site->domain }} {{ $site->getAliasesString() }};
        return 301 https://$host$request_uri;
    }
@endif

@php
    $backendName = preg_replace("/[^A-Za-z0-9 ]/", '', $site->domain).'_backend';
@endphp

@if ($site->type === \App\Enums\SiteType::LOAD_BALANCER)
    upstream {{ $backendName }} {
        @switch($site->type_data['method'] ?? \App\Enums\LoadBalancerMethod::ROUND_ROBIN)
            @case(\App\Enums\LoadBalancerMethod::LEAST_CONNECTIONS)
                least_conn;
                @break
            @case(\App\Enums\LoadBalancerMethod::IP_HASH)
                ip_hash;
                @break
            @default
        @endswitch
        @if ($site->loadBalancerServers()->count() > 0)
            @foreach($site->loadBalancerServers as $server)
                server {{ $server->ip }}:{{ $server->port }} {{ $server->backup ? 'backup' : '' }} {{ $server->weight ? 'weight='.$server->weight : '' }};
            @endforeach
        @else
            server 127.0.0.1;
        @endif
    }
@endif

@if ($site->activeSsl)
@foreach($site->activeSsl as $ssl)
server {
    @include('ssh.services.webserver.nginx.vhost-server-content', ['site' => $site,'ssl' => $ssl,'domains' => $ssl->domains])
}
@endforeach
@endif

server {
    @include('ssh.services.webserver.nginx.vhost-server-content', ['site' => $site,'ssl' => null,'domains' => array_merge([$site->domain], $site->aliases)])
}
