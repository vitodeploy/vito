# vito-managed: {!! $addresses->pluck('ip')->implode(',') !!}
[Unit]
Description=Vito managed IP addresses
After=network-online.target systemd-networkd.service
Wants=network-online.target
PartOf=systemd-networkd.service

[Service]
Type=oneshot
RemainAfterExit=yes
@foreach ($addresses as $address)
ExecStart=-ip addr replace {!! $address->ip !!}/{!! $address->prefix_length !!} dev {!! $address->interface !!}
@endforeach

[Install]
WantedBy=multi-user.target
