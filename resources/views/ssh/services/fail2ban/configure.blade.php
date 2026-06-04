@include('ssh.services.fail2ban.jail')

if ! sudo fail2ban-client -t; then
    echo "VITO_SSH_ERROR: invalid fail2ban configuration"
    exit 1
fi

sudo fail2ban-client reload
