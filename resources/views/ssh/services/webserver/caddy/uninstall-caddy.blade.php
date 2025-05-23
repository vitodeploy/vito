sudo service caddy stop

sudo DEBIAN_FRONTEND=noninteractive sudo apt remove caddy -y

sudo rm -rf /etc/caddy
sudo rm -rf /var/log/caddy
sudo rm -rf /var/lib/caddy
sudo rm -rf /var/cache/caddy
sudo rm -rf /usr/share/caddy
sudo rm -rf /etc/systemd/system/caddy.service

sudo systemctl daemon-reload
