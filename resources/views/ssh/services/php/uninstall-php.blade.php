sudo service php{{ $version }}-fpm stop

if ! sudo DEBIAN_FRONTEND=noninteractive apt-get remove -y php{{ $version }} php{{ $version }}-fpm php{{ $version }}-mbstring php{{ $version }}-mysql php{{ $version }}-mcrypt php{{ $version }}-gd php{{ $version }}-xml php{{ $version }}-curl php{{ $version }}-gettext php{{ $version }}-zip php{{ $version }}-bcmath php{{ $version }}-soap php{{ $version }}-redis php{{ $version }}-sqlite3; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
