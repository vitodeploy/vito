if ! sudo echo '__ini__' > /etc/php/__version__/cli/php.ini; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo service php__version__-fpm restart; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
