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
        $isV6Source = isset($rule->source) && $rule->source !== null && \App\Support\Cidr::isV6($rule->source);
    @endphp

@if($isV6Source)
    if grep -q '^IPV6=yes' /etc/default/ufw 2>/dev/null; then
        if ! sudo ufw {{ $rule->type }} from {{ $source }} to any{{ $protocol }}{{ $port }}; then
            @include('ssh.services.firewall.ufw.restore-rules')
            echo 'VITO_SSH_ERROR' && exit 1
        fi
    fi
@else
    if ! sudo ufw {{ $rule->type }} from {{ $source }} to any{{ $protocol }}{{ $port }}; then
        @include('ssh.services.firewall.ufw.restore-rules')
        echo 'VITO_SSH_ERROR' && exit 1
    fi
@endif
@endforeach

if ! sudo ufw --force enable; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo ufw reload; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

@include('ssh.services.firewall.ufw.clear-backups')
