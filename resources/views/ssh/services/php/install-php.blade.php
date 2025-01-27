sudo add-apt-repository ppa:ondrej/php -y

sudo DEBIAN_FRONTEND=noninteractive apt-get update

if ! sudo DEBIAN_FRONTEND=noninteractive apt-get install -y php{{ $version }} php{{ $version }}-fpm php{{ $version }}-mbstring php{{ $version }}-mysql php{{ $version }}-gd php{{ $version }}-xml php{{ $version }}-curl php{{ $version }}-gettext php{{ $version }}-zip php{{ $version }}-bcmath php{{ $version }}-soap php{{ $version }}-redis php{{ $version }}-sqlite3 php{{ $version }}-tokenizer php{{ $version }}-pgsql php{{ $version }}-pdo; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo sed -i 's/www-data/{{ $user }}/g' /etc/php/{{ $version }}/fpm/pool.d/www.conf; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

sudo service php{{ $version }}-fpm enable

sudo service php{{ $version }}-fpm start
