sudo add-apt-repository ppa:ondrej/php -y

sudo DEBIAN_FRONTEND=noninteractive apt-get update

if ! sudo DEBIAN_FRONTEND=noninteractive apt-get install -y php__version__ php__version__-fpm php__version__-mbstring php__version__-mysql php__version__-gd php__version__-xml php__version__-curl php__version__-gettext php__version__-zip php__version__-bcmath php__version__-soap php__version__-redis php__version__-sqlite3 php__version__-tokenizer php__version__-pgsql php__version__-pdo; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo sed -i 's/www-data/__user__/g' /etc/php/__version__/fpm/pool.d/www.conf; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

sudo service php__version__-fpm enable

sudo service php__version__-fpm start
