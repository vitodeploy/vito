[Interface]
Address = {{ $address }}/{{ $prefix }}
ListenPort = {{ $listenPort }}
PrivateKey = {{ $privateKey }}
@foreach ($peers as $peer)

[Peer]
PublicKey = {{ $peer['public_key'] }}
AllowedIPs = {{ $peer['allowed_ips'] }}
@if (! empty($peer['endpoint']))
Endpoint = {{ $peer['endpoint'] }}
PersistentKeepalive = 25
@endif
@endforeach
