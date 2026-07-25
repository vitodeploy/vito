@include('ssh.services.firewall.ufw.backup-rules')

if ! sudo ufw --force reset; then
    echo 'VITO_SSH_ERROR' && exit 1
fi


if ! sudo ufw default deny incoming; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo ufw default allow outgoing; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

@foreach($rules as $rule)
    @php
        $source = isset($rule->source) && $rule->source !== null
            ? $rule->source . (isset($rule->mask) && $rule->mask !== null ? '/' . $rule->mask : '')
            : 'any';
        $protocol = isset($rule->protocol) && $rule->protocol !== null && $rule->protocol !== ''
            ? ' proto ' . $rule->protocol
            : '';
        $port = isset($rule->port) && $rule->port !== null && $rule->port !== ''
            ? ' port ' . $rule->port
            : '';
    @endphp

    if ! sudo ufw {{ $rule->type }} from {{ $source }} to any{{ $protocol }}{{ $port }}; then
        @include('ssh.services.firewall.ufw.restore-rules')
        echo 'VITO_SSH_ERROR' && exit 1
    fi
@endforeach

if ! sudo ufw --force enable; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo ufw reload; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

@include('ssh.services.firewall.ufw.clear-backups')
