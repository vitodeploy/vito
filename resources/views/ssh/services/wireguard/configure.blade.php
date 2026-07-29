sudo mkdir -p /etc/wireguard
sudo chmod 700 /etc/wireguard

sudo install -m 600 -o root -g root /etc/wireguard/wg-vito-{{ $networkId }}.conf.tmp /etc/wireguard/wg-vito-{{ $networkId }}.conf
sudo rm -f /etc/wireguard/wg-vito-{{ $networkId }}.conf.tmp

sudo systemctl enable wg-quick@wg-vito-{{ $networkId }}
sudo systemctl restart wg-quick@wg-vito-{{ $networkId }}

if ! sudo wg show wg-vito-{{ $networkId }} >/dev/null 2>&1; then
    echo "VITO_SSH_ERROR: wireguard interface wg-vito-{{ $networkId }} failed to start"
    exit 1
fi

@php $keep = implode(' ', array_map('intval', $keepNetworkIds)); @endphp
for conf in /etc/wireguard/wg-vito-*.conf; do
    [ -e "$conf" ] || continue
    id=$(basename "$conf" .conf)
    id=${id#wg-vito-}
    case " {{ $keep }} " in
        *" $id "*) continue ;;
    esac
    sudo systemctl stop wg-quick@wg-vito-"$id" 2>/dev/null || true
    sudo systemctl disable wg-quick@wg-vito-"$id" 2>/dev/null || true
    sudo wg-quick down wg-vito-"$id" 2>/dev/null || true
    sudo rm -f "$conf"
done
