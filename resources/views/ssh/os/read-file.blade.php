if [ -f {!! escapeshellarg($path) !!} ]; then sudo head -c {{ (int) $limit + 1 }} {!! escapeshellarg($path) !!}; fi
