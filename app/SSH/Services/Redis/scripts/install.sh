sudo DEBIAN_FRONTEND=noninteractive apt-get install redis-server -y

sudo sed -i 's/bind 127.0.0.1 ::1/bind 0.0.0.0/g' /etc/redis/redis.conf

sudo service redis enable

sudo service redis start
