export DEBIAN_FRONTEND=noninteractive
if id -u {{ $user }} >/dev/null 2>&1; then
    if [ "$(id -u {{ $user }})" -lt 1000 ] \
        || ! id -nG {{ $serverUser }} | tr ' ' '\n' | grep -qx {{ $user }} \
        || id -nG {{ $user }} | tr ' ' '\n' | grep -qxE 'root|sudo|admin|wheel|docker|lxd|incus|disk|shadow' \
        || ! LC_ALL=C sudo -n -l -U {{ $user }} 2>&1 | grep -q "is not allowed to run sudo"; then
        echo "User {{ $user }} already exists but was not created by Vito, is a system account, or has elevated privileges, so it cannot be used as an isolated user."
        echo 'VITO_SSH_ERROR' && exit 1
    fi
    echo "User {{ $user }} already exists, reasserting directories and permissions."
    VITO_USER_CREATED=0
else
    if ! sudo useradd -p $(openssl passwd -1 {{ $password }}) {{ $user }}; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi
    sudo usermod -a -G {{ $user }} {{ $serverUser }}
    VITO_USER_CREATED=1
fi

sudo mkdir -p /home/{{ $user }}
sudo chown {{ $user }}:{{ $user }} /home/{{ $user }}
sudo chmod 750 /home/{{ $user }}
sudo chsh -s /bin/bash {{ $user }}
sudo -u {{ $user }} bash -s -- {!! $key !!} <<'VITO_ISOLATED_HOME'
set -e
cd /home/{{ $user }}
mkdir -p .logs tmp bin .ssh
touch .ssh/authorized_keys .bashrc .profile
grep -qxF "$1" .ssh/authorized_keys || printf '%s\n' "$1" >> .ssh/authorized_keys
PATH_LINE='export PATH="/home/{{ $user }}/bin:$PATH"'
grep -qxF "$PATH_LINE" .bashrc || echo "$PATH_LINE" >> .bashrc
grep -qxF "$PATH_LINE" .profile || echo "$PATH_LINE" >> .profile
chmod 700 .ssh .logs tmp
chmod 600 .ssh/authorized_keys
VITO_ISOLATED_HOME
if [ "$VITO_USER_CREATED" = "1" ]; then
    echo "Created user {{ $user }}."
fi
unset VITO_USER_CREATED
