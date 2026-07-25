export DEBIAN_FRONTEND=noninteractive

sudo apt-get remove -y wireguard wireguard-tools || true
sudo apt-get autoremove -y || true
