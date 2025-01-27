rm -rf {{ $path }}

sudo rm /etc/nginx/sites-available/{{ $domain }}

sudo rm /etc/nginx/sites-enabled/{{ $domain }}

echo "Site deleted"
