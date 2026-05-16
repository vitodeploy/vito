set -e

if [ ! -d {{ $dir }} ]; then
    echo "Creating basic auth directory {{ $dir }}"
    sudo mkdir -p {{ $dir }}
    sudo chown root:root {{ $dir }}
    sudo chmod 755 {{ $dir }}
fi

sudo tee {{ $path }} > /dev/null <<'BASIC_AUTH_EOF'
@foreach ($lines as $line){!! $line !!}
@endforeach
BASIC_AUTH_EOF

sudo chown root:{{ $nginxUser }} {{ $path }}
sudo chmod 640 {{ $path }}

echo "Wrote basic auth file {{ $path }} with {{ $userCount }} user(s): {{ $usernames }}"
sudo ls -l {{ $path }}
