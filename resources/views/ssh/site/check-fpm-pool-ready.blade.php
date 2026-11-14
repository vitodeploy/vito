test -S /run/php/php{{ $version }}-fpm-{{ $user }}.sock &&
sudo systemctl is-active --quiet php{{ $version }}-fpm
