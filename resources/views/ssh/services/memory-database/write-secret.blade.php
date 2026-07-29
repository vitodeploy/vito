if sudo test -f {{ $include }}; then
    sudo cp {{ $include }} {{ $include }}.vito.bak
else
    sudo rm -f {{ $include }}.vito.bak
fi

sudo install -o {{ $owner }} -g {{ $owner }} -m 600 /dev/null {{ $include }}
@if ($withSecret)

printf 'requirepass "%s"\n' "$VITO_MEMDB_PASSWORD" | sudo tee {{ $include }} > /dev/null
@endif
