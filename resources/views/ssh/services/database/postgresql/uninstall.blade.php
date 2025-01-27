sudo service postgresql stop

sudo DEBIAN_FRONTEND=noninteractive apt-get remove postgresql-* -y

sudo rm -rf /etc/postgresql
sudo rm -rf /var/lib/postgresql
sudo rm -rf /var/log/postgresql
sudo rm -rf /var/run/postgresql
sudo rm -rf /var/run/postgresql/postmaster.pid
sudo rm -rf /var/run/postgresql/.s.PGSQL.5432
sudo rm -rf /var/run/postgresql/.s.PGSQL.5432.lock
