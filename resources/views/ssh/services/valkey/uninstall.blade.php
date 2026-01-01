sudo systemctl stop valkey-server

sudo DEBIAN_FRONTEND=noninteractive apt-get remove valkey-server -y

sudo rm -rf /etc/valkey
sudo rm -rf /var/lib/valkey
sudo rm -rf /var/log/valkey
sudo rm -rf /var/run/valkey

sudo DEBIAN_FRONTEND=noninteractive sudo apt-get autoremove -y

sudo DEBIAN_FRONTEND=noninteractive sudo apt-get autoclean -y
