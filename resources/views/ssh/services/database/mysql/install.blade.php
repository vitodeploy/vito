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
sudo DEBIAN_FRONTEND=noninteractive apt-get install -y wget lsb-release gnupg dirmngr ca-certificates

# Import the MySQL signing key, preferring keyservers (which carry a refreshed
# expiry) and falling back to the armored keys published on the MySQL repo.
MYSQL_KEY_ID="B7B3B788A8D3785C"
MYSQL_KEYRING="/usr/share/keyrings/mysql-archive-keyring.gpg"

import_mysql_key() {
    for server in keyserver.ubuntu.com keys.openpgp.org pgp.mit.edu; do
        for attempt in 1 2 3; do
            if gpg --batch --keyserver "hkps://${server}" --recv-keys "${MYSQL_KEY_ID}"; then
                gpg --export "${MYSQL_KEY_ID}" | sudo tee "${MYSQL_KEYRING}" > /dev/null
                return 0
            fi
            sleep 2
        done
    done

    for url in \
        "https://repo.mysql.com/RPM-GPG-KEY-mysql-2023" \
        "https://repo.mysql.com/RPM-GPG-KEY-mysql-2022"; do
        if wget -qO- "${url}" | sudo gpg --dearmor -o "${MYSQL_KEYRING}"; then
            return 0
        fi
    done

    return 1
}

if ! import_mysql_key; then
    echo 'VITO_SSH_ERROR: failed to import MySQL GPG key' && exit 1
fi

# Add the MySQL {{ $version }} LTS repository
CODENAME=$(lsb_release -sc)
echo "deb [signed-by=${MYSQL_KEYRING}] http://repo.mysql.com/apt/ubuntu ${CODENAME} mysql-{{ $version }}-lts" | sudo tee /etc/apt/sources.list.d/mysql.list

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
