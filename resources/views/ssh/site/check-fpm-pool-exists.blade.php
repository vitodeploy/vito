test -f /etc/php/{{ $version }}/fpm/pool.d/{{ $user }}.conf &&
test -S /run/php/php{{ $version }}-fpm-{{ $user }}.sock &&
sudo systemctl is-active --quiet php{{ $version }}-fpm
