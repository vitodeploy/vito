sudo service mysql stop

sudo DEBIAN_FRONTEND=noninteractive apt-get remove mysql-server -y

sudo rm -rf /etc/mysql
sudo rm -rf /var/lib/mysql
sudo rm -rf /var/log/mysql
sudo rm -rf /var/run/mysqld
sudo rm -rf /var/run/mysqld/mysqld.sock
