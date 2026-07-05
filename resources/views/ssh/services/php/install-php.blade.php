sudo add-apt-repository ppa:ondrej/php -y

sudo DEBIAN_FRONTEND=noninteractive apt-get update -o Acquire::AllowReleaseInfoChange::Label=true

if ! sudo DEBIAN_FRONTEND=noninteractive apt-get install -y php{{ $version }} php{{ $version }}-fpm php{{ $version }}-mbstring php{{ $version }}-mysql php{{ $version }}-gd php{{ $version }}-xml php{{ $version }}-curl php{{ $version }}-gettext php{{ $version }}-zip php{{ $version }}-bcmath php{{ $version }}-soap php{{ $version }}-redis php{{ $version }}-sqlite3 php{{ $version }}-tokenizer php{{ $version }}-pgsql php{{ $version }}-pdo php{{ $version }}-intl; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo sed -i 's/www-data/{{ $user }}/g' /etc/php/{{ $version }}/fpm/pool.d/www.conf; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

sudo systemctl enable php{{ $version }}-fpm

sudo systemctl start php{{ $version }}-fpm
