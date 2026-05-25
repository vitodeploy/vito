if ! sudo test -f {!! escapeshellarg($source) !!}; then echo VITO_NO_FILE; exit 0; fi
sudo cat {!! escapeshellarg($source) !!} > {!! escapeshellarg($dest) !!}
chmod 600 {!! escapeshellarg($dest) !!}
