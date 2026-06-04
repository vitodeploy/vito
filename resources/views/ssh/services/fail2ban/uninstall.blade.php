export DEBIAN_FRONTEND=noninteractive

sudo fail2ban-client unban --all > /dev/null 2>&1 || true
sudo systemctl disable --now fail2ban > /dev/null 2>&1 || true
sudo apt-get purge -y fail2ban > /dev/null 2>&1 || true
sudo apt-get autoremove -y > /dev/null 2>&1 || true
sudo rm -f /etc/fail2ban/jail.d/vito.local
