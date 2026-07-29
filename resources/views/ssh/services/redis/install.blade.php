sudo DEBIAN_FRONTEND=noninteractive apt-get install redis-server -y

sudo systemctl enable redis-server

sudo systemctl start redis-server
