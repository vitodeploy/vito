curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' | sudo gpg --batch --yes --dearmor -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg

curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt' | \

sudo tee /etc/apt/sources.list.d/caddy-stable.list

sudo DEBIAN_FRONTEND=noninteractive apt-get install -y \
    debian-keyring debian-archive-keyring apt-transport-https curl

sudo DEBIAN_FRONTEND=noninteractive apt-get update -y

sudo DEBIAN_FRONTEND=noninteractive apt-get install caddy certbot -y

sudo mkdir /etc/caddy/sites-available

sudo mkdir /etc/caddy/sites-enabled
