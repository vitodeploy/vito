@php($sudo = isset($user) ? 'sudo -u '.escapeshellarg($user) : 'sudo')
if ! {!! $sudo !!} test -f {!! escapeshellarg($path) !!}; then echo VITO_NO_FILE; exit 0; fi
set -o pipefail
{!! $sudo !!} tail -c {{ (int) $bytes }} {!! escapeshellarg($path) !!} | tail -n {{ (int) $lines }}
