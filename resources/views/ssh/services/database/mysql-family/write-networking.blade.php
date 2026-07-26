sudo rm -f {{ $dropIn }}.vito.bak

sudo mkdir -p {{ $directory }}

if sudo test -f {{ $dropIn }}; then
    sudo cp {{ $dropIn }} {{ $dropIn }}.vito.bak
else
    sudo install -m 644 /dev/null {{ $dropIn }}.vito.bak
fi

printf '[mysqld]\nbind-address = %s\n' '{{ $address }}' | sudo tee {{ $dropIn }} > /dev/null
@if ($managesXPlugin)

printf 'loose-mysqlx-bind-address = %s\n' '{{ $address }}' | sudo tee -a {{ $dropIn }} > /dev/null
@endif
