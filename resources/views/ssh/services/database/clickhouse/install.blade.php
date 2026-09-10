#!/bin/bash

# Stop and purge any existing ClickHouse installation
sudo systemctl stop clickhouse-server 2>/dev/null || true

sudo DEBIAN_FRONTEND=noninteractive apt-get purge -y clickhouse-server clickhouse-client clickhouse-common-static 2>/dev/null || true
sudo DEBIAN_FRONTEND=noninteractive apt-get autoremove -y 2>/dev/null || true
sudo DEBIAN_FRONTEND=noninteractive apt-get autoclean 2>/dev/null || true

# Remove old repository and keys
sudo rm -f /etc/apt/sources.list.d/clickhouse.list
sudo rm -f /usr/share/keyrings/clickhouse-keyring.gpg

sudo DEBIAN_FRONTEND=noninteractive apt-get update
sudo DEBIAN_FRONTEND=noninteractive apt-get install -y wget curl lsb-release gnupg dirmngr ca-certificates apt-transport-https

CLICKHOUSE_KEYRING="/usr/share/keyrings/clickhouse-keyring.gpg"

import_clickhouse_key() {
    if curl -fsSL "https://packages.clickhouse.com/rpm/lts/repodata/repomd.xml.key" | sudo gpg --batch --yes --dearmor -o "${CLICKHOUSE_KEYRING}"; then
        return 0
    fi

    for server in keyserver.ubuntu.com keys.openpgp.org pgp.mit.edu; do
        for attempt in 1 2 3; do
            if gpg --batch --keyserver "hkps://${server}" --recv-keys "8919F6BD2B48D754"; then
                gpg --export "8919F6BD2B48D754" | sudo tee "${CLICKHOUSE_KEYRING}" > /dev/null
                return 0
            fi
            sleep 2
        done
    done

    return 1
}

if ! import_clickhouse_key; then
    echo 'VITO_SSH_ERROR: failed to import ClickHouse GPG key' && exit 1
fi

ARCH=$(dpkg --print-architecture)
echo "deb [signed-by=${CLICKHOUSE_KEYRING} arch=${ARCH}] https://packages.clickhouse.com/deb stable main" | sudo tee /etc/apt/sources.list.d/clickhouse.list

sudo DEBIAN_FRONTEND=noninteractive apt-get update -y

if ! sudo DEBIAN_FRONTEND=noninteractive apt-get install -y clickhouse-server={{ $version }}.* clickhouse-client={{ $version }}.* 2>/dev/null; then
    sudo DEBIAN_FRONTEND=noninteractive apt-get install -y clickhouse-server clickhouse-client
fi

sudo mkdir -p /etc/clickhouse-server/users.d
sudo mkdir -p /etc/clickhouse-server/config.d
sudo mkdir -p /var/lib/clickhouse/backups
sudo chown -R clickhouse:clickhouse /var/lib/clickhouse/backups 2>/dev/null || true

cat << 'EOF' | sudo tee /etc/clickhouse-server/users.d/access_management.xml > /dev/null
<clickhouse>
    <users>
        <default>
            <access_management>1</access_management>
            <networks>
                <ip>127.0.0.1</ip>
                <ip>::1</ip>
            </networks>
        </default>
    </users>
</clickhouse>
EOF

cat << 'EOF' | sudo tee /etc/clickhouse-server/config.d/backups.xml > /dev/null
<clickhouse>
    <storage_configuration>
        <disks>
            <backups>
                <type>local</type>
                <path>/var/lib/clickhouse/backups/</path>
            </backups>
        </disks>
    </storage_configuration>
    <backups>
        <allowed_disk>backups</allowed_disk>
        <allowed_path>/var/lib/clickhouse/backups/</allowed_path>
    </backups>
</clickhouse>
EOF

sudo systemctl enable clickhouse-server
sudo systemctl restart clickhouse-server

if ! sudo clickhouse-client -q "SELECT version();"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
