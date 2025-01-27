sudo service nginx stop

sudo DEBIAN_FRONTEND=noninteractive apt-get purge nginx nginx-common nginx-full -y

sudo rm -rf /etc/nginx
sudo rm -rf /var/log/nginx
sudo rm -rf /var/lib/nginx
sudo rm -rf /var/cache/nginx
sudo rm -rf /usr/share/nginx
sudo rm -rf /etc/systemd/system/nginx.service

sudo systemctl daemon-reload
