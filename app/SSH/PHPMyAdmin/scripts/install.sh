sudo rm -rf phpmyadmin

sudo rm -rf __path__

if ! wget https://files.phpmyadmin.net/phpMyAdmin/__version__/phpMyAdmin-__version__-all-languages.zip; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! unzip phpMyAdmin-__version__-all-languages.zip; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! rm -rf phpMyAdmin-__version__-all-languages.zip; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! mv phpMyAdmin-__version__-all-languages __path__; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! mv __path__/config.sample.inc.php __path__/config.inc.php; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "PHPMyAdmin installed!"
