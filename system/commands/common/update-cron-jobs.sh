if ! echo '__data__' | sudo -u __user__ crontab -; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo -u __user__ crontab -l; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
