wget -O mariadb_repo_setup https://downloads.mariadb.com/MariaDB/mariadb_repo_setup

chmod +x mariadb_repo_setup

if ! sudo DEBIAN_FRONTEND=noninteractive ./mariadb_repo_setup \
    --mariadb-server-version="mariadb-{{ $version }}" \
    --skip-maxscale; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

sudo DEBIAN_FRONTEND=noninteractive apt-get update

sudo DEBIAN_FRONTEND=noninteractive apt-get install mariadb-server mariadb-backup -y

sudo systemctl unmask mysql.service

sudo service mysql start
