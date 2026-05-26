wget -qO - https://deb.goaccess.io/gnugpg.key | sudo gpg --batch --yes --dearmor -o /usr/share/keyrings/goaccess.gpg

echo "deb [signed-by=/usr/share/keyrings/goaccess.gpg arch=$(dpkg --print-architecture)] https://deb.goaccess.io/ $(lsb_release -cs) main" | sudo tee /etc/apt/sources.list.d/goaccess.list

sudo DEBIAN_FRONTEND=noninteractive apt-get update -y

sudo DEBIAN_FRONTEND=noninteractive apt-get install goaccess jq -y

sudo mkdir -p /var/lib/goaccess

sudo chown {{ $user }}:{{ $user }} /var/lib/goaccess
