@php($sudo = isset($user) ? 'sudo -u '.escapeshellarg($user) : 'sudo')
if ! {!! $sudo !!} test -f {!! escapeshellarg($path) !!}; then echo VITO_NO_FILE; exit 0; fi
{!! $sudo !!} tail -n {{ $lines }} {!! escapeshellarg($path) !!}
