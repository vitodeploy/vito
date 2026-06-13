sudo service mariadb stop 2>/dev/null || true
sudo service mysql stop 2>/dev/null || true
sudo systemctl stop mariadb 2>/dev/null || true
sudo systemctl stop mysql 2>/dev/null || true

sudo DEBIAN_FRONTEND=noninteractive apt-get remove mariadb-server mariadb-backup -y

sudo rm -rf /etc/mysql
sudo rm -rf /var/lib/mysql
sudo rm -rf /var/log/mysql
sudo rm -rf /var/run/mysqld
sudo rm -rf /var/run/mysqld/mysqld.sock
