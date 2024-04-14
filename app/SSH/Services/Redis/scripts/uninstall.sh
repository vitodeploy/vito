sudo service redis stop

sudo DEBIAN_FRONTEND=noninteractive apt-get remove redis-server -y

sudo rm -rf /etc/redis
sudo rm -rf /var/lib/redis
sudo rm -rf /var/log/redis
sudo rm -rf /var/run/redis
sudo rm -rf /var/run/redis/redis-server.pid
sudo rm -rf /var/run/redis/redis-server.sock
sudo rm -rf /var/run/redis/redis-server.sock

sudo DEBIAN_FRONTEND=noninteractive sudo apt-get autoremove -y

sudo DEBIAN_FRONTEND=noninteractive sudo apt-get autoclean -y
