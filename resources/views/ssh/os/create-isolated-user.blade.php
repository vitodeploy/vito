export DEBIAN_FRONTEND=noninteractive
if id -u {{ $user }} >/dev/null 2>&1; then
    echo "User {{ $user }} already exists, skipping creation."
    exit 0
fi
if ! sudo useradd -p $(openssl passwd -1 {{ $password }}) {{ $user }}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

sudo mkdir -p /home/{{ $user }}
sudo mkdir -p /home/{{ $user }}/.logs
sudo mkdir -p /home/{{ $user }}/tmp
sudo mkdir -p /home/{{ $user }}/bin
sudo mkdir -p /home/{{ $user }}/.ssh
echo 'export PATH="/home/{{ $user }}/bin:$PATH"' | sudo tee -a /home/{{ $user }}/.bashrc
echo 'export PATH="/home/{{ $user }}/bin:$PATH"' | sudo tee -a /home/{{ $user }}/.profile
if ! id -nG {{ $serverUser }} | tr ' ' '\n' | grep -qx {{ $user }}; then
    sudo usermod -a -G {{ $user }} {{ $serverUser }}
fi
sudo chown -R {{ $user }}:{{ $user }} /home/{{ $user }}
sudo chmod 750 /home/{{ $user }}
sudo chmod 700 /home/{{ $user }}/.ssh
sudo chmod 700 /home/{{ $user }}/.logs
sudo chmod 700 /home/{{ $user }}/tmp
sudo chsh -s /bin/bash {{ $user }}
echo "Created user {{ $user }}."
