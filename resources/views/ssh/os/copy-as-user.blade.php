sudo cat {!! escapeshellarg($source) !!} > {!! escapeshellarg($dest) !!}
chmod 600 {!! escapeshellarg($dest) !!}
