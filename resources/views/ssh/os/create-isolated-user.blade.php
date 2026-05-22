export DEBIAN_FRONTEND=noninteractive
if id -u {{ $user }} >/dev/null 2>&1; then
    echo "User {{ $user }} already exists, reasserting directories and permissions."
    VITO_USER_CREATED=0
else
    if ! sudo useradd -p $(openssl passwd -1 {{ $password }}) {{ $user }}; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi
    VITO_USER_CREATED=1
fi

sudo mkdir -p /home/{{ $user }}
sudo mkdir -p /home/{{ $user }}/.logs
sudo mkdir -p /home/{{ $user }}/tmp
sudo mkdir -p /home/{{ $user }}/bin
sudo mkdir -p /home/{{ $user }}/.ssh
sudo touch /home/{{ $user }}/.ssh/authorized_keys
VITO_PUBKEY={!! $key !!}
if ! sudo grep -qxF "$VITO_PUBKEY" /home/{{ $user }}/.ssh/authorized_keys; then
    printf '%s\n' "$VITO_PUBKEY" | sudo tee -a /home/{{ $user }}/.ssh/authorized_keys
fi
unset VITO_PUBKEY
PATH_LINE='export PATH="/home/{{ $user }}/bin:$PATH"'
sudo touch /home/{{ $user }}/.bashrc /home/{{ $user }}/.profile
grep -qxF "$PATH_LINE" /home/{{ $user }}/.bashrc || echo "$PATH_LINE" | sudo tee -a /home/{{ $user }}/.bashrc
grep -qxF "$PATH_LINE" /home/{{ $user }}/.profile || echo "$PATH_LINE" | sudo tee -a /home/{{ $user }}/.profile
if ! id -nG {{ $serverUser }} | tr ' ' '\n' | grep -qx {{ $user }}; then
    sudo usermod -a -G {{ $user }} {{ $serverUser }}
fi
sudo chown -R {{ $user }}:{{ $user }} /home/{{ $user }}
sudo chmod 750 /home/{{ $user }}
sudo chmod 700 /home/{{ $user }}/.ssh
sudo chmod 600 /home/{{ $user }}/.ssh/authorized_keys
sudo chmod 700 /home/{{ $user }}/.logs
sudo chmod 700 /home/{{ $user }}/tmp
sudo chsh -s /bin/bash {{ $user }}
if [ "$VITO_USER_CREATED" = "1" ]; then
    echo "Created user {{ $user }}."
fi
unset VITO_USER_CREATED
