if ! sudo sed -i 's,^{{ $variable }} =.*$,{{ $variable }} = {{ $value }},' /etc/php/{{ $version }}/cli/php.ini; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo sed -i 's,^{{ $variable }} =.*$,{{ $variable }} = {{ $value }},' /etc/php/{{ $version }}/fpm/php.ini; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo service php{{ $version }}-fpm restart; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
