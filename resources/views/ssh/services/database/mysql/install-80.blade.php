#!/bin/bash

# Stop and purge any existing MySQL installation
sudo systemctl stop mysql 2>/dev/null || true
sudo systemctl stop mysql.service 2>/dev/null || true

sudo DEBIAN_FRONTEND=noninteractive apt-get purge -y mysql-server mysql-server-* mysql-client mysql-common mysql-community-server 2>/dev/null || true
sudo DEBIAN_FRONTEND=noninteractive apt-get autoremove -y 2>/dev/null || true
sudo DEBIAN_FRONTEND=noninteractive apt-get autoclean 2>/dev/null || true

# Remove old repository and keys
sudo rm -f /etc/apt/sources.list.d/mysql.list
sudo rm -f /usr/share/keyrings/mysql-archive-keyring.gpg
sudo rm -f /etc/apt/trusted.gpg.d/mysql.gpg

sudo DEBIAN_FRONTEND=noninteractive apt-get update

sudo DEBIAN_FRONTEND=noninteractive apt-get install -y wget lsb-release gnupg

# Fetch the updated MySQL GPG key from keyserver and export to proper keyring location
gpg --keyserver hkps://keyserver.ubuntu.com --recv-keys B7B3B788A8D3785C
gpg --export B7B3B788A8D3785C | sudo tee /usr/share/keyrings/mysql-archive-keyring.gpg > /dev/null

# Manually add MySQL 8.0 repository
CODENAME=$(lsb_release -sc)
echo "deb [signed-by=/usr/share/keyrings/mysql-archive-keyring.gpg] http://repo.mysql.com/apt/ubuntu ${CODENAME} mysql-8.0" | sudo tee /etc/apt/sources.list.d/mysql.list

sudo DEBIAN_FRONTEND=noninteractive apt-get update

sudo DEBIAN_FRONTEND=noninteractive \
    apt-get -o Dpkg::Options::="--force-confdef" \
            -o Dpkg::Options::="--force-confold" \
    install -y mysql-server

sudo systemctl unmask mysql.service
sudo systemctl enable mysql
sudo systemctl start mysql

if ! sudo mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH auth_socket;"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo mysql -e "FLUSH PRIVILEGES"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
