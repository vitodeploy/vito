sudo DEBIAN_FRONTEND=noninteractive apt-get install valkey-server -y

sudo sed -i 's/bind 127.0.0.1 -::1/bind 0.0.0.0/g' /etc/valkey/valkey.conf

sudo systemctl enable valkey-server

sudo systemctl start valkey-server
