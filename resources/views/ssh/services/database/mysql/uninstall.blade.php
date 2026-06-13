sudo systemctl stop mysql 2>/dev/null || true
sudo systemctl stop mysql.service 2>/dev/null || true

sudo DEBIAN_FRONTEND=noninteractive apt-get purge --remove -y mysql-server mysql-server-* mysql-client mysql-common mysql-community-server 2>/dev/null || true
sudo DEBIAN_FRONTEND=noninteractive apt-get autoremove --purge -y 2>/dev/null || true
sudo DEBIAN_FRONTEND=noninteractive apt-get autoclean -y 2>/dev/null || true

sudo rm -f /etc/apt/trusted.gpg.d/mysql.gpg
sudo rm -f /usr/share/keyrings/mysql-archive-keyring.gpg
sudo rm -rf /var/lib/apt/lists/*mysql*
sudo rm -rf /var/lib/dpkg/info/mysql*
sudo rm -rf /var/cache/apt/archives/*mysql*

sudo rm -rf /etc/mysql
sudo rm -rf /var/lib/mysql
sudo rm -rf /var/log/mysql
sudo rm -rf /var/run/mysqld
sudo rm -rf /var/run/mysqld/mysqld.sock
sudo rm -f /etc/apt/sources.list.d/mysql.list

sudo rm -rf /var/lib/apt/lists/*
sudo DEBIAN_FRONTEND=noninteractive apt-get update
