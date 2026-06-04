export DEBIAN_FRONTEND=noninteractive

sudo add-apt-repository -y universe
sudo apt-get update -y
sudo apt-get install -y fail2ban

@include('ssh.services.fail2ban.jail')

if ! sudo fail2ban-client -t; then
    echo "VITO_SSH_ERROR: invalid fail2ban configuration"
    exit 1
fi

sudo systemctl enable fail2ban
sudo systemctl restart fail2ban
