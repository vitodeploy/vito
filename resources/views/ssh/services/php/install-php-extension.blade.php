sudo apt-get install -y php{{ $version }}-{{ $name }}

sudo service php{{ $version }}-fpm restart

php{{ $version }} -m
