set -e
available={!! escapeshellarg('/etc/'.$name.'/sites-available/'.$file) !!}
enabled={!! escapeshellarg('/etc/'.$name.'/sites-enabled/'.$file) !!}
had_previous=0
if sudo test -f "$available"; then
    sudo cp "$available" "$available.previous"
    had_previous=1
fi
rollback() {
    if [ "$had_previous" = 1 ]; then
        sudo mv "$available.previous" "$available"
        sudo ln -sfn "$available" "$enabled"
    else
        sudo rm -f "$enabled" "$available"
    fi
    sudo rm -f "$available.pending"
    echo 'VITO_SSH_ERROR'
    exit 1
}
trap rollback ERR
@if($remove)
sudo rm -f "$enabled" "$available"
@else
sudo mv "$available.pending" "$available"
sudo ln -sfn "$available" "$enabled"
@endif
@if($name === 'nginx')
sudo nginx -t
@else
sudo caddy validate --config /etc/caddy/Caddyfile
@endif
sudo service {{ $name }} reload
sudo rm -f "$available.previous"
