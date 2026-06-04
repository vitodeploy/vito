@if($useDropin)
sudo mkdir -p /etc/ssh/sshd_config.d
sudo tee /etc/ssh/sshd_config.d/00-vito-root-login.conf > /dev/null <<'EOF'
PermitRootLogin no
EOF
@else
sudo sed -i -E '/^[[:space:]]*#?[[:space:]]*PermitRootLogin[[:space:]]/d' /etc/ssh/sshd_config
printf 'PermitRootLogin no\n' | sudo tee -a /etc/ssh/sshd_config > /dev/null
@endif

if ! sudo sshd -t; then
    echo "VITO_SSH_ERROR: invalid sshd configuration"
    exit 1
fi

EFFECTIVE=$(sudo sshd -T 2>/dev/null | awk '/^permitrootlogin /{print $2; exit}')
if [ "$EFFECTIVE" != "no" ]; then
    echo "VITO_SSH_ERROR: root login is still enabled (effective: ${EFFECTIVE:-unknown})"
    exit 1
fi

sudo systemctl reload ssh
