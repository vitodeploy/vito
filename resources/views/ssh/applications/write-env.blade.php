set -e
@if ($operation === 'prepare')
umask 077
set -C
: > {!! escapeshellarg($envTemp) !!}
: > {!! escapeshellarg($loaderTemp) !!}
@elseif ($operation === 'install')
chmod 600 -- {!! escapeshellarg($envTemp) !!}
chmod 700 -- {!! escapeshellarg($loaderTemp) !!}
mv -f -- {!! escapeshellarg($loaderTemp) !!} {!! escapeshellarg($loader) !!}
mv -f -- {!! escapeshellarg($envTemp) !!} {!! escapeshellarg($path) !!}
@elseif ($operation === 'cleanup')
rm -f -- {!! escapeshellarg($envTemp) !!} {!! escapeshellarg($loaderTemp) !!}
@endif
