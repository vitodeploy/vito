sudo rm -f /etc/php/{{ $version }}/fpm/pool.d/{{ $user }}.conf
sudo service php{{ $version }}-fpm restart
