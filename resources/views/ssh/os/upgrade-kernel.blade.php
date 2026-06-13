sudo DEBIAN_FRONTEND=noninteractive NEEDRESTART_MODE=a apt-get clean
sudo DEBIAN_FRONTEND=noninteractive NEEDRESTART_MODE=a apt-get update
sudo DEBIAN_FRONTEND=noninteractive NEEDRESTART_MODE=a apt-get dist-upgrade -y
sudo DEBIAN_FRONTEND=noninteractive NEEDRESTART_MODE=a apt-get autoremove -y
