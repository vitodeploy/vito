@if($useDropin)
sudo mkdir -p /etc/ssh/sshd_config.d
sudo tee /etc/ssh/sshd_config.d/00-vito-hardening.conf > /dev/null <<'EOF'
PasswordAuthentication no
KbdInteractiveAuthentication no
PubkeyAuthentication yes
EOF
@else
sudo sed -i -E '/^[[:space:]]*#?[[:space:]]*(PasswordAuthentication|KbdInteractiveAuthentication|ChallengeResponseAuthentication)[[:space:]]/d' /etc/ssh/sshd_config
printf 'PasswordAuthentication no\nKbdInteractiveAuthentication no\nPubkeyAuthentication yes\n' | sudo tee -a /etc/ssh/sshd_config > /dev/null
@endif

if ! sudo sshd -t; then
    echo "VITO_SSH_ERROR: invalid sshd configuration"
    exit 1
fi

EFFECTIVE=$(sudo sshd -T 2>/dev/null | awk '/^passwordauthentication /{print $2; exit}')
if [ "$EFFECTIVE" != "no" ]; then
    echo "VITO_SSH_ERROR: password authentication is still enabled (effective: ${EFFECTIVE:-unknown})"
    exit 1
fi

KBD_EFFECTIVE=$(sudo sshd -T 2>/dev/null | awk '/^kbdinteractiveauthentication /{print $2; exit}')
if [ "$KBD_EFFECTIVE" != "no" ]; then
    echo "VITO_SSH_ERROR: keyboard-interactive authentication is still enabled (effective: ${KBD_EFFECTIVE:-unknown})"
    exit 1
fi

sudo systemctl reload ssh
