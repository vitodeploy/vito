sudo rm -rf phpmyadmin

if ! wget https://files.phpmyadmin.net/phpMyAdmin/5.1.2/phpMyAdmin-5.1.2-all-languages.zip; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! unzip phpMyAdmin-5.1.2-all-languages.zip; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! rm -rf phpMyAdmin-5.1.2-all-languages.zip; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! mv phpMyAdmin-5.1.2-all-languages phpmyadmin; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! mv phpmyadmin/config.sample.inc.php phpmyadmin/config.inc.php; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
