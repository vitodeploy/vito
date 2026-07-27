if sudo test -f {{ $conf }}; then
    sudo cp {{ $conf }} {{ $conf }}.vito.bak
fi

sudo sed -i -E 's/^([[:space:]]*bind[[:space:]]+)(0\.0\.0\.0|\*).*$/\1127.0.0.1/' {{ $conf }}

[ -n "$(sudo tail -c1 {{ $conf }})" ] && printf '\n' | sudo tee -a {{ $conf }} > /dev/null || true

if sudo test -f {{ $include }}; then
    sudo cp {{ $include }} {{ $include }}.vito.bak
else
    sudo rm -f {{ $include }}.vito.bak
fi

sudo install -o {{ $owner }} -g {{ $owner }} -m 600 /dev/null {{ $include }}

printf 'requirepass "%s"\n' "$VITO_MEMDB_PASSWORD" | sudo tee {{ $include }} > /dev/null

sudo sed -i '/^# BEGIN VITO NETWORKING$/,/^# END VITO NETWORKING$/d' {{ $conf }}

printf '# BEGIN VITO NETWORKING\nbind 0.0.0.0\ninclude %s\n# END VITO NETWORKING\n' '{{ $include }}' | sudo tee -a {{ $conf }} > /dev/null
