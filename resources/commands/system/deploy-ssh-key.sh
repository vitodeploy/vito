if ! echo '__key__' | sudo tee -a ~/.ssh/authorized_keys; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
