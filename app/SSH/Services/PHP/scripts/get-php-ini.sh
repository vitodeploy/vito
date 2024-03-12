if ! cat /etc/php/__version__/cli/php.ini; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
