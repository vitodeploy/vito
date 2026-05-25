sudo a2dissite {!! escapeshellarg($domain.'.conf') !!} > /dev/null 2>&1 || true

sudo rm -f /etc/apache2/sites-available/{{ $domain }}.conf

sudo rm -rf {{ $path }}

echo "Site deleted"
