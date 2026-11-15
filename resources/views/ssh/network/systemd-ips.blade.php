# vito-managed: {!! $addresses->pluck('ip')->implode(',') !!}
[Unit]
Description=Vito managed IP addresses
After=network-online.target systemd-networkd.service
Wants=network-online.target
PartOf=systemd-networkd.service

[Service]
Type=oneshot
RemainAfterExit=yes
Restart=on-failure
RestartSec=10s
ExecStart=/bin/sh -c '\
    status=0; \
@foreach ($addresses as $address)
    ip addr replace {!! $address->ip !!}/{!! $address->prefix_length !!} dev {!! $address->interface !!} || status=1; \
@endforeach
    exit "$$status"'

[Install]
WantedBy=multi-user.target
