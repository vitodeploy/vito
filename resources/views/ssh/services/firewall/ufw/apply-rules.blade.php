@if ($requireIpv6 ?? false)
if ! grep -q '^IPV6=yes' /etc/default/ufw 2>/dev/null; then
    echo 'Resource access requires IPv4 and IPv6 firewall protection.'
    echo 'VITO_SSH_ERROR' && exit 1
fi
@endif
@if (isset($resourceRules) && $resourceRules->isNotEmpty())
if ! sudo tee /etc/vito-resource-guard.new >/dev/null <<'VITO_RESOURCE_SCRIPT'
@foreach ([4, 6] as $family)
if ! sudo ip{{ $family === 6 ? '6' : '' }}tables-restore --wait --noflush <<'VITO_RESOURCE_GUARD'
*raw
:vito-resource-guard - [0:0]
-F vito-resource-guard
-A vito-resource-guard -i lo -j RETURN
@foreach ($resourceRules as $rule)
@if ($rule->source === null || (\App\Support\Cidr::isV6($rule->source) ? 6 : 4) === $family)
-A vito-resource-guard -p tcp @if ($rule->source !== null)-s {{ $rule->source }}/{{ $rule->mask }} @endif--dport {{ $rule->port }} -j {{ $rule->type === 'allow' ? 'RETURN' : 'DROP' }}
@endif
@endforeach
COMMIT
VITO_RESOURCE_GUARD
then
    echo 'VITO_SSH_ERROR' && exit 1
fi
if ! sudo ip{{ $family === 6 ? '6' : '' }}tables --wait -t raw -C PREROUTING -m addrtype --dst-type LOCAL -j vito-resource-guard 2>/dev/null; then
    if ! sudo ip{{ $family === 6 ? '6' : '' }}tables --wait -t raw -I PREROUTING 1 -m addrtype --dst-type LOCAL -j vito-resource-guard; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi
fi
@endforeach
VITO_RESOURCE_SCRIPT
then
    echo 'VITO_SSH_ERROR' && exit 1
fi
if ! sudo chmod 700 /etc/vito-resource-guard.new || ! sudo mv /etc/vito-resource-guard.new /etc/vito-resource-guard; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
if ! sudo tee /etc/systemd/system/vito-resource-guard.service >/dev/null <<'VITO_RESOURCE_UNIT'
[Unit]
Description=Vito resource firewall guard
DefaultDependencies=no
After=local-fs.target
Before=network-pre.target ufw.service mysql.service mariadb.service postgresql.service redis-server.service valkey-server.service
Wants=network-pre.target
[Service]
Type=oneshot
ExecStart=/bin/bash /etc/vito-resource-guard
RemainAfterExit=yes
[Install]
WantedBy=multi-user.target
VITO_RESOURCE_UNIT
then
    echo 'VITO_SSH_ERROR' && exit 1
fi
for unit in mysql.service mariadb.service postgresql.service postgresql@.service redis-server.service valkey-server.service; do
    if ! sudo mkdir -p "/etc/systemd/system/$unit.d" || ! sudo tee "/etc/systemd/system/$unit.d/50-vito-resource-guard.conf" >/dev/null <<'VITO_RESOURCE_DEPENDENCY'
[Unit]
Requires=vito-resource-guard.service
After=vito-resource-guard.service
VITO_RESOURCE_DEPENDENCY
    then
        echo 'VITO_SSH_ERROR' && exit 1
    fi
done
if ! sudo systemctl daemon-reload || ! sudo systemctl enable vito-resource-guard.service || ! sudo bash /etc/vito-resource-guard; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
@endif
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

@if (! isset($resourceRules) || $resourceRules->isEmpty())
for executable in iptables ip6tables; do
    if sudo "$executable" --wait -t raw -C PREROUTING -m addrtype --dst-type LOCAL -j vito-resource-guard 2>/dev/null; then
        if ! sudo "$executable" --wait -t raw -D PREROUTING -m addrtype --dst-type LOCAL -j vito-resource-guard || ! sudo "$executable" --wait -t raw -F vito-resource-guard || ! sudo "$executable" --wait -t raw -X vito-resource-guard; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi
    fi
done
for unit in mysql.service mariadb.service postgresql.service postgresql@.service redis-server.service valkey-server.service; do
    if ! sudo rm -f "/etc/systemd/system/$unit.d/50-vito-resource-guard.conf"; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi
done
if ! sudo systemctl daemon-reload; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
if [ -f /etc/systemd/system/vito-resource-guard.service ]; then
    if ! sudo systemctl disable --now vito-resource-guard.service || ! sudo rm -f /etc/systemd/system/vito-resource-guard.service /etc/vito-resource-guard || ! sudo systemctl daemon-reload; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi
fi
@endif
@include('ssh.services.firewall.ufw.clear-backups')
