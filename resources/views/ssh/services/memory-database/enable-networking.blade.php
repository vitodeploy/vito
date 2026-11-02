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

bind='bind 0.0.0.0'
if grep -q . /proc/net/if_inet6 2>/dev/null; then
    bind='bind 0.0.0.0 ::'
fi
version="$({{ $owner }}-server --version 2>/dev/null | sed -nE 's/.*v=([0-9]+)\.([0-9]+).*/\1 \2/p')"
if [ -n "$version" ]; then
    major="${version%% *}"
    minor="${version##* }"
    if [ "$major" -gt 6 ] || { [ "$major" -eq 6 ] && [ "$minor" -ge 2 ]; }; then
        bind='bind 0.0.0.0 -::'
    fi
fi

printf '# BEGIN VITO NETWORKING\n%s\ninclude %s\n# END VITO NETWORKING\n' "$bind" '{{ $include }}' | sudo tee -a {{ $conf }} > /dev/null
