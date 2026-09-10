sudo systemctl stop clickhouse-server 2>/dev/null || true
sudo systemctl disable clickhouse-server 2>/dev/null || true

sudo DEBIAN_FRONTEND=noninteractive apt-get purge --remove -y clickhouse-server clickhouse-client clickhouse-common-static
sudo DEBIAN_FRONTEND=noninteractive apt-get autoremove --purge -y 2>/dev/null || true
sudo DEBIAN_FRONTEND=noninteractive apt-get autoclean -y 2>/dev/null || true

sudo rm -f /usr/share/keyrings/clickhouse-keyring.gpg
sudo rm -f /etc/apt/sources.list.d/clickhouse.list
sudo rm -rf /etc/clickhouse-server
sudo rm -rf /var/lib/clickhouse
sudo rm -rf /var/log/clickhouse-server

sudo rm -rf /var/lib/apt/lists/*
sudo DEBIAN_FRONTEND=noninteractive apt-get update
