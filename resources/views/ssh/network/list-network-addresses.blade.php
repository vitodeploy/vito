sudo ip -j addr show
echo '===VITO-MANAGED==='
sudo grep -h '# vito-managed:' {{ $isDebian ? '/etc/systemd/system/vito-ips.service' : '/etc/netplan/60-vito.yaml' }} 2>/dev/null || true
