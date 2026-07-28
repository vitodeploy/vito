if cat {!! escapeshellarg($tmpPath) !!} > {!! escapeshellarg($path) !!}; then
    rm -f {!! escapeshellarg($tmpPath) !!}
else
    rm -f {!! escapeshellarg($tmpPath) !!}
    exit 1
fi
