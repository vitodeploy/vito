sudo systemctl stop wg-quick@wg-vito-{{ $networkId }} 2>/dev/null || true
sudo systemctl disable wg-quick@wg-vito-{{ $networkId }} 2>/dev/null || true
sudo wg-quick down wg-vito-{{ $networkId }} 2>/dev/null || true
sudo rm -f /etc/wireguard/wg-vito-{{ $networkId }}.conf
