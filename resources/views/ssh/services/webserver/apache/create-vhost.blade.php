if ! sudo a2ensite {!! escapeshellarg($domain.'.conf') !!} > /dev/null; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo apachectl configtest; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo service apache2 reload; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
