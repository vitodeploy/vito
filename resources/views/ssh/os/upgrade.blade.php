sudo DEBIAN_FRONTEND=noninteractive NEEDRESTART_MODE=a apt-get clean
sudo DEBIAN_FRONTEND=noninteractive NEEDRESTART_MODE=a apt-get update
sudo DEBIAN_FRONTEND=noninteractive NEEDRESTART_MODE=a apt-get upgrade -y -o Dpkg::Options::="--force-confdef" -o Dpkg::Options::="--force-confold"
sudo DEBIAN_FRONTEND=noninteractive NEEDRESTART_MODE=a apt-get autoremove -y
