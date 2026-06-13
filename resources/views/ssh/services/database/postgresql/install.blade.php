#!/bin/bash

# Stop and purge any existing PostgreSQL installation
sudo systemctl stop postgresql 2>/dev/null || true
sudo service postgresql stop 2>/dev/null || true

sudo DEBIAN_FRONTEND=noninteractive apt-get purge -y postgresql-* 2>/dev/null || true
sudo DEBIAN_FRONTEND=noninteractive apt-get autoremove -y 2>/dev/null || true
sudo DEBIAN_FRONTEND=noninteractive apt-get autoclean 2>/dev/null || true

# Remove old repository and keys
sudo rm -f /etc/apt/sources.list.d/pgdg.list
sudo rm -f /usr/share/keyrings/postgresql-archive-keyring.gpg
sudo apt-key del ACCC4CF8 2>/dev/null || true

sudo DEBIAN_FRONTEND=noninteractive apt-get update
sudo DEBIAN_FRONTEND=noninteractive apt-get install -y wget lsb-release gnupg dirmngr ca-certificates locales

# Ensure a UTF-8 locale exists so the cluster is initialised as UTF8 regardless of the server locale
sudo locale-gen en_US.UTF-8
sudo update-locale LANG=en_US.UTF-8
export LANG=en_US.UTF-8
export LC_ALL=en_US.UTF-8

# Import the PostgreSQL signing key, preferring the official armored key with a keyserver fallback
PG_KEY_ID="7FCC7D46ACCC4CF8"
PG_KEY_URL="https://www.postgresql.org/media/keys/ACCC4CF8.asc"
PG_KEYRING="/usr/share/keyrings/postgresql-archive-keyring.gpg"

import_postgresql_key() {
    if wget --quiet --tries=3 --timeout=15 -O /tmp/pgdg-key.asc "${PG_KEY_URL}" && [ -s /tmp/pgdg-key.asc ]; then
        sudo gpg --batch --yes --dearmor -o "${PG_KEYRING}" /tmp/pgdg-key.asc
        rm -f /tmp/pgdg-key.asc
        return 0
    fi

    for server in keyserver.ubuntu.com keys.openpgp.org pgp.mit.edu; do
        if gpg --batch --keyserver "hkps://${server}" --recv-keys "${PG_KEY_ID}"; then
            gpg --export "${PG_KEY_ID}" | sudo tee "${PG_KEYRING}" > /dev/null
            return 0
        fi
    done

    return 1
}

if ! import_postgresql_key; then
    echo 'VITO_SSH_ERROR: failed to import PostgreSQL GPG key' && exit 1
fi

# Add the PGDG repository with the signed-by directive
CODENAME=$(lsb_release -cs)
echo "deb [signed-by=${PG_KEYRING}] https://apt.postgresql.org/pub/repos/apt ${CODENAME}-pgdg main" | sudo tee /etc/apt/sources.list.d/pgdg.list

sudo DEBIAN_FRONTEND=noninteractive apt-get update -y

sudo DEBIAN_FRONTEND=noninteractive apt-get install -y postgresql-{{ $version }}

# Recreate the auto-created cluster with an explicit UTF8 encoding/locale
sudo pg_dropcluster --stop {{ $version }} main 2>/dev/null || true
sudo pg_createcluster --start --locale en_US.UTF-8 {{ $version }} main -- --encoding=UTF8

sudo systemctl enable postgresql 2>/dev/null || true
sudo systemctl restart postgresql

sudo systemctl status postgresql --no-pager || true

if ! sudo -u postgres psql -c "SELECT version();"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
