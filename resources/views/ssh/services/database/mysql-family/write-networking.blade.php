sudo mkdir -p {{ $directory }}

printf '[mysqld]\nbind-address = %s\n' '{{ $address }}' | sudo tee {{ $dropIn }} > /dev/null
@if ($managesXPlugin)

printf 'loose-mysqlx-bind-address = %s\n' '{{ $address }}' | sudo tee -a {{ $dropIn }} > /dev/null
@endif
