rm -rf {{ $path }}

sudo rm /etc/caddy/sites-available/{{ $domain }}

sudo rm /etc/caddy/sites-enabled/{{ $domain }}

echo "Site deleted"
