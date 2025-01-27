export DEBIAN_FRONTEND=noninteractive
if ! sudo useradd -p $(openssl passwd -1 {{ $password }}) {{ $user }}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

sudo mkdir /home/{{ $user }}
sudo mkdir /home/{{ $user }}/.logs
sudo mkdir /home/{{ $user }}/tmp
sudo mkdir /home/{{ $user }}/bin
sudo mkdir /home/{{ $user }}/.ssh
echo 'export PATH="/home/{{ $user }}/bin:$PATH"' | sudo tee -a /home/{{ $user }}/.bashrc
echo 'export PATH="/home/{{ $user }}/bin:$PATH"' | sudo tee -a /home/{{ $user }}/.profile
sudo usermod -a -G {{ $user }} {{ $serverUser }}
sudo chown -R {{ $user }}:{{ $user }} /home/{{ $user }}
sudo chmod -R 755 /home/{{ $user }}
sudo chmod -R 700 /home/{{ $user }}/.ssh
sudo chsh -s /bin/bash {{ $user }}
echo "Created user {{ $user }}."
