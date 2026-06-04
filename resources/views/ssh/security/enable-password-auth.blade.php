@if($useDropin)
sudo rm -f /etc/ssh/sshd_config.d/00-vito-hardening.conf
@else
sudo sed -i -E '/^[[:space:]]*#?[[:space:]]*(PasswordAuthentication|KbdInteractiveAuthentication)[[:space:]]/d' /etc/ssh/sshd_config
printf 'PasswordAuthentication yes\nKbdInteractiveAuthentication yes\n' | sudo tee -a /etc/ssh/sshd_config > /dev/null
@endif

if ! sudo sshd -t; then
    echo "VITO_SSH_ERROR: invalid sshd configuration"
    exit 1
fi

EFFECTIVE=$(sudo sshd -T 2>/dev/null | awk '/^passwordauthentication /{print $2; exit}')
if [ "$EFFECTIVE" != "yes" ]; then
    echo "VITO_SSH_ERROR: password authentication is still disabled (effective: ${EFFECTIVE:-unknown})"
    exit 1
fi

sudo systemctl reload ssh
