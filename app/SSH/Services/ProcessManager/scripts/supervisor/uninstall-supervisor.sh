sudo service supervisor stop

sudo DEBIAN_FRONTEND=noninteractive apt-get remove supervisor -y

sudo rm -rf /etc/supervisor
sudo rm -rf /var/log/supervisor
sudo rm -rf /var/run/supervisor
sudo rm -rf /var/run/supervisor/supervisor.sock
