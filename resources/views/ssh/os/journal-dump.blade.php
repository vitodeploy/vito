sudo journalctl -u {!! escapeshellarg($unit) !!} --no-pager --output=short-iso > {!! escapeshellarg($path) !!}
chmod 600 {!! escapeshellarg($path) !!}
