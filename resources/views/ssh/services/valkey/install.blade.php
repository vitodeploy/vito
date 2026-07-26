sudo DEBIAN_FRONTEND=noninteractive apt-get install valkey-server -y

sudo systemctl enable valkey-server

sudo systemctl start valkey-server
