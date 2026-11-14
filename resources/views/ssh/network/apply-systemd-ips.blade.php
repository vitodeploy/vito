@foreach ($removed as $address)
sudo ip addr del {!! escapeshellarg($address->ip.'/'.$address->prefix_length) !!} dev {!! escapeshellarg($address->interface) !!} 2>/dev/null || true
@endforeach
@if ($addresses->isNotEmpty())
sudo systemctl daemon-reload
sudo systemctl enable vito-ips.service
sudo systemctl restart vito-ips.service
@foreach ($addresses as $address)
if [ -z "$(sudo ip -o addr show to {!! escapeshellarg($address->ip) !!})" ]; then
    echo "VITO_SSH_ERROR: "{!! escapeshellarg($address->ip) !!}" could not be added to "{!! escapeshellarg($address->interface) !!}
    exit 1
fi
@endforeach
@else
sudo systemctl disable --now vito-ips.service 2>/dev/null || true
sudo rm -f /etc/systemd/system/vito-ips.service
sudo systemctl daemon-reload
@endif
