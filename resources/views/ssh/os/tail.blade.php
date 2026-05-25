if ! sudo test -f {{ $path }}; then echo VITO_NO_FILE; exit 0; fi
sudo tail -n {{ $lines }} {{ $path }}
