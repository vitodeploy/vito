if ! curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! chmod +x wp-cli.phar; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if [ "__is_isolated__" == "true" ]; then
    if ! mv wp-cli.phar /home/__isolated_username__/bin/wp; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi
else
    if ! mv wp-cli.phar /usr/local/bin/wp; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi
fi

rm -rf __path__

if ! wp --path=__path__ core download; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! wp --path=__path__ core config \
    --dbname="__db_name__" \
    --dbuser="__db_user__" \
    --dbpass="__db_pass__" \
    --dbhost="__db_host__" \
    --dbprefix="__db_prefix__"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! wp --path=__path__ core install \
    --url="http://__domain__" \
    --title="__title__" \
    --admin_user="__username__" \
    --admin_password="__password__" \
    --admin_email="__email__"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
