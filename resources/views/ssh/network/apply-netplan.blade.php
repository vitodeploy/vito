@if ($hasManaged)
sudo chmod 600 /etc/netplan/60-vito.yaml
@else
sudo rm -f /etc/netplan/60-vito.yaml
@endif
sudo netplan generate
sudo netplan apply
