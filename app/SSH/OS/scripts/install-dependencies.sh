sudo DEBIAN_FRONTEND=noninteractive apt-get install -y software-properties-common curl zip unzip git gcc openssl
git config --global user.email "__email__"
git config --global user.name "__name__"

# Install Node.js
curl -fsSL https://deb.nodesource.com/setup_lts.x | sudo -E bash -;
sudo DEBIAN_FRONTEND=noninteractive apt-get update
sudo DEBIAN_FRONTEND=noninteractive apt-get install nodejs -y
