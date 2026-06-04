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

sudo systemctl reload ssh
