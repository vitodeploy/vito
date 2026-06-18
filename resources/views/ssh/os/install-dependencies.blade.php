sudo DEBIAN_FRONTEND=noninteractive NEEDRESTART_MODE=a apt-get -o Dpkg::Options::="--force-confdef" -o Dpkg::Options::="--force-confold" install -y software-properties-common curl zip unzip git gcc openssl ufw cron
git config --global user.email "{{ $email }}"
git config --global user.name "{{ $name }}"

# Install Mise
sudo install -dm 755 /etc/apt/keyrings
curl -fsSL https://mise.jdx.dev/gpg-key.pub | sudo tee /etc/apt/keyrings/mise-archive-keyring.pub 1> /dev/null
echo "deb [signed-by=/etc/apt/keyrings/mise-archive-keyring.pub arch=$(dpkg --print-architecture)] https://mise.jdx.dev/deb stable main" | sudo tee /etc/apt/sources.list.d/mise.list
sudo DEBIAN_FRONTEND=noninteractive NEEDRESTART_MODE=a apt-get update
sudo DEBIAN_FRONTEND=noninteractive NEEDRESTART_MODE=a apt-get -o Dpkg::Options::="--force-confdef" -o Dpkg::Options::="--force-confold" install -y mise
