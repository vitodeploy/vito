sudo rm -f {{ $dropIn }}.vito.bak

sudo mkdir -p {{ $directory }}

if sudo test -f {{ $dropIn }}; then
    sudo cp {{ $dropIn }} {{ $dropIn }}.vito.bak
else
    sudo install -m 644 /dev/null {{ $dropIn }}.vito.bak
fi

printf '<clickhouse>\n    <listen_host>%s</listen_host>\n</clickhouse>\n' '{{ $address }}' | sudo tee {{ $dropIn }} > /dev/null
