@php($sudo = isset($user) ? 'sudo -u '.escapeshellarg($user) : 'sudo')
if ! {!! $sudo !!} test -f {!! escapeshellarg($path) !!}; then echo VITO_NO_FILE; exit 0; fi
{!! $sudo !!} tail -c {{ (int) $scanBytes }} {!! escapeshellarg($path) !!} 2>/dev/null | grep -F -i -- {!! escapeshellarg($term) !!} | tail -n {{ (int) $lines }} | tail -c {{ (int) $bytes }}
