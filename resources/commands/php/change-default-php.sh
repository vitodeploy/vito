if ! sudo rm /usr/bin/php; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo ln -s /usr/bin/php__version__ /usr/bin/php; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "Default php is: "

php -v
