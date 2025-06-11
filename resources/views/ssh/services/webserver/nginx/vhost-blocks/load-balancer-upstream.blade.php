#[load-balancer-upstream]
@php
    $backendName = preg_replace("/[^A-Za-z0-9 ]/", '', $site->domain).'_backend';
@endphp
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
#[/load-balancer-upstream]
