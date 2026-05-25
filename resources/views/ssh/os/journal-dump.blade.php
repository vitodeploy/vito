sudo journalctl -u {!! escapeshellarg($unit) !!} --no-pager --output=short-iso 2>/dev/null > {!! escapeshellarg($path) !!}
chmod 600 {!! escapeshellarg($path) !!}
