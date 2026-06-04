sudo service apache2 stop

sudo DEBIAN_FRONTEND=noninteractive apt-get purge apache2 apache2-utils apache2-bin apache2-data -y

sudo DEBIAN_FRONTEND=noninteractive apt-get autoremove -y

sudo rm -rf /etc/apache2
sudo rm -rf /var/log/apache2
sudo rm -rf /var/www/vito-splash
sudo rm -rf /etc/systemd/system/apache2.service.d

sudo systemctl daemon-reload
