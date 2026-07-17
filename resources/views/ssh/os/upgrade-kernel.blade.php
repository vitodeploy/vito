sudo DEBIAN_FRONTEND=noninteractive NEEDRESTART_MODE=a apt-get clean
sudo DEBIAN_FRONTEND=noninteractive NEEDRESTART_MODE=a apt-get update -o Acquire::AllowReleaseInfoChange::Label=true
sudo DEBIAN_FRONTEND=noninteractive NEEDRESTART_MODE=a apt-get -o Dpkg::Options::="--force-confdef" -o Dpkg::Options::="--force-confold" dist-upgrade -y
sudo DEBIAN_FRONTEND=noninteractive NEEDRESTART_MODE=a apt-get autoremove -y
