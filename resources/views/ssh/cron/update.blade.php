if ! echo '{!! $cron !!}' | sudo -u {{ $user }} crontab -; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo -u {{ $user }} crontab -l; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo 'cron updated!'
