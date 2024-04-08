wget -c https://dev.mysql.com/get/mysql-apt-config_0.8.22-1_all.deb

sudo DEBIAN_FRONTEND=noninteractive dpkg -i mysql-apt-config_0.8.22-1_all.deb

sudo DEBIAN_FRONTEND=noninteractive apt-get update

sudo DEBIAN_FRONTEND=noninteractive apt-get install mysql-server -y

sudo systemctl unmask mysql.service

sudo service mysql enable

sudo service mysql start

if ! sudo mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH auth_socket;"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo mysql -e "FLUSH PRIVILEGES"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
