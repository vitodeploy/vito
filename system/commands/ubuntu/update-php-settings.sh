if ! sudo sed -i 's,^__variable__ =.*$,__variable__ = __value__,' /etc/php/__version__/cli/php.ini; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo sed -i 's,^__variable__ =.*$,__variable__ = __value__,' /etc/php/__version__/fpm/php.ini; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo service php__version__-fpm restart; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
