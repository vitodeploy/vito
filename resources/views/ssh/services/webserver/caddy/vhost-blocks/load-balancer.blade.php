#[load-balancer]
reverse_proxy {
    @if ($site->loadBalancerServers()->count() > 0)
        @foreach($site->loadBalancerServers as $server)
            to {{ $server->ip }}:{{ $server->port }}
        @endforeach
    @else
        to 127.0.0.1
    @endif
    @switch($site->type_data['method'] ?? LoadBalancerMethod::ROUND_ROBIN)
        @case(LoadBalancerMethod::LEAST_CONNECTIONS)
            lb_policy least_conn
            @break
        @case(LoadBalancerMethod::IP_HASH)
            lb_policy ip_hash
            @break
        @default
            lb_policy round_robin
    @endswitch
    header_up Host {host}
    header_up X-Real-IP {remote}
}
#[/load-balancer]
