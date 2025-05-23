# Add Caddy's GPG key and repository
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' | sudo gpg --dearmor -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg

curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt' | \

sudo tee /etc/apt/sources.list.d/caddy-stable.list

# Install required packages
sudo DEBIAN_FRONTEND=noninteractive apt-get install -y \
    debian-keyring debian-archive-keyring apt-transport-https curl

# Update package list
sudo DEBIAN_FRONTEND=noninteractive apt-get update -y

# Install Caddy
sudo DEBIAN_FRONTEND=noninteractive apt-get install caddy -y

sudo mkdir /etc/caddy/sites-available

sudo mkdir /etc/caddy/sites-enabled
