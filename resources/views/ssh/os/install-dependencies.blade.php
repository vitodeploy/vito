sudo DEBIAN_FRONTEND=noninteractive apt-get install -y software-properties-common curl zip unzip git gcc openssl ufw
git config --global user.email "{{ $email }}"
git config --global user.name "{{ $name }}"

# Install Node.js
curl -fsSL https://deb.nodesource.com/setup_lts.x | sudo -E bash -
sudo DEBIAN_FRONTEND=noninteractive apt-get update
sudo DEBIAN_FRONTEND=noninteractive apt-get install nodejs -y
