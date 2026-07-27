if sudo test -f {{ $conf }}; then
    sudo cp {{ $conf }} {{ $conf }}.vito.bak
fi

if sudo test -f {{ $hba }}; then
    sudo cp {{ $hba }} {{ $hba }}.vito.bak
fi

sudo grep -Eq '^[[:space:]]*include_dir[[:space:]]*=' {{ $conf }} || printf "\ninclude_dir = 'conf.d'\n" | sudo tee -a {{ $conf }} > /dev/null

sudo mkdir -p {{ $directory }}

printf "listen_addresses = '%s'\n" '{{ $address }}' | sudo tee {{ $dropIn }} > /dev/null

sudo sed -i '/^# BEGIN VITO NETWORKING$/,/^# END VITO NETWORKING$/d' {{ $hba }}
@if ($open)

[ -n "$(sudo tail -c1 {{ $hba }})" ] && printf '\n' | sudo tee -a {{ $hba }} > /dev/null || true

printf '# BEGIN VITO NETWORKING\nhost all postgres 0.0.0.0/0 reject\nhost all all 0.0.0.0/0 scram-sha-256\n# END VITO NETWORKING\n' | sudo tee -a {{ $hba }} > /dev/null
@endif
