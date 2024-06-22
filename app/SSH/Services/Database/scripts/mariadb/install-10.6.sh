wget https://downloads.mariadb.com/MariaDB/mariadb_repo_setup

chmod +x mariadb_repo_setup

sudo DEBIAN_FRONTEND=noninteractive ./mariadb_repo_setup \
   --mariadb-server-version="mariadb-10.6"

sudo DEBIAN_FRONTEND=noninteractive apt-get update

sudo DEBIAN_FRONTEND=noninteractive apt-get install mariadb-server mariadb-backup -y

sudo systemctl unmask mysql.service

sudo service mysql start
