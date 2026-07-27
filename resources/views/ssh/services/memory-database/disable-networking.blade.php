if sudo test -f {{ $conf }}; then
    sudo cp {{ $conf }} {{ $conf }}.vito.bak
fi

sudo sed -i -E 's/^([[:space:]]*bind[[:space:]]+)(0\.0\.0\.0|\*).*$/\1127.0.0.1/' {{ $conf }}

[ -n "$(sudo tail -c1 {{ $conf }})" ] && printf '\n' | sudo tee -a {{ $conf }} > /dev/null || true

sudo test -f {{ $include }} || sudo install -o {{ $owner }} -g {{ $owner }} -m 600 /dev/null {{ $include }}

sudo sed -i '/^# BEGIN VITO NETWORKING$/,/^# END VITO NETWORKING$/d' {{ $conf }}

printf '# BEGIN VITO NETWORKING\nbind 127.0.0.1\ninclude %s\n# END VITO NETWORKING\n' '{{ $include }}' | sudo tee -a {{ $conf }} > /dev/null
