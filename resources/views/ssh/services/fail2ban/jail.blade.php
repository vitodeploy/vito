ADMIN_IP=$(echo "${SSH_CLIENT:-}" | awk '{print $1}')
IGNOREIP="127.0.0.1/8 ::1"
if [ -n "$ADMIN_IP" ]; then
    IGNOREIP="$IGNOREIP $ADMIN_IP"
fi
@if($ignoreip !== '')
IGNOREIP="$IGNOREIP {{ $ignoreip }}"
@endif

sudo mkdir -p /etc/fail2ban/jail.d
sudo tee /etc/fail2ban/jail.d/vito.local > /dev/null <<EOF
[DEFAULT]
bantime = {{ $bantime }}
findtime = {{ $findtime }}
maxretry = {{ $maxretry }}
ignoreip = $IGNOREIP

[sshd]
enabled = true
port = {{ $port }}
backend = systemd
maxretry = {{ $maxretry }}
findtime = {{ $findtime }}
bantime = {{ $bantime }}
EOF
