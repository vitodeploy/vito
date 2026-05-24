if ! sudo test -f {!! escapeshellarg($path) !!}; then echo VITO_NO_FILE; exit 0; fi
sudo tail -n 100000 {!! escapeshellarg($path) !!} 2>/dev/null | grep -F -i -- {!! escapeshellarg($term) !!} | tail -n {{ (int) $lines }}
