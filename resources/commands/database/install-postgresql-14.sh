DEBIAN_FRONTEND=noninteractive sh -c 'echo "deb http://apt.postgresql.org/pub/repos/apt $(lsb_release -cs)-pgdg main" > /etc/apt/sources.list.d/pgdg.list'

DEBIAN_FRONTEND=noninteractive wget --quiet -O - https://www.postgresql.org/media/keys/ACCC4CF8.asc | apt-key add -

sudo DEBIAN_FRONTEND=noninteractive apt-get update -y

sudo DEBIAN_FRONTEND=noninteractive apt install postgresql-14 -y

systemctl status postgresql

sudo -u postgres psql -c "SELECT version();"
