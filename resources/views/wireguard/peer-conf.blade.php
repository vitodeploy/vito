[Interface]
Address = {{ $address }}/{{ $prefix }}
PrivateKey = {{ $privateKey }}
@foreach ($peers as $peer)

[Peer]
PublicKey = {{ $peer['public_key'] }}
AllowedIPs = {{ $peer['allowed_ips'] }}
Endpoint = {{ $peer['endpoint'] }}
PersistentKeepalive = 25
@endforeach
