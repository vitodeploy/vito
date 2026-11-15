@if ($unitCount > 0)
STATES=$(timeout 10 bash -c {!! $unitsArgument !!} 2>/dev/null || true)

[ "$(printf '%s\n' "$STATES" | wc -l)" = "{{ $unitCount }}" ] || STATES=""

@foreach ($services as $service)
@if ($service['stateIndex'] !== null)
STATE_{{ $service['id'] }}=$(printf '%s\n' "$STATES" | sed -n '{{ $service['stateIndex'] }}p')

@endif
@endforeach
@endif
@foreach ($services as $service)
@if ($service['stateIndex'] !== null)
echo "###VITO:{{ $service['id'] }}:status###"

printf '%s\n' "$STATE_{{ $service['id'] }}"

@endif
@if ($service['versionFragment'] !== null)
OUT=$(timeout 15 bash -c {!! $service['versionFragment'] !!} 2>/dev/null || true)

echo "###VITO:{{ $service['id'] }}:version###"

printf '%s\n' "$OUT"

@endif
@if ($service['networkingFragment'] !== null)
@if ($service['networkingRequiresRunning'])
if [ "$STATE_{{ $service['id'] }}" = "active" ]; then

echo "###VITO:{{ $service['id'] }}:networking###"

OUT=$(timeout 15 bash -c {!! $service['networkingFragment'] !!} 2>/dev/null || true)

printf '%s\n' "$OUT"

fi

@else
OUT=$(timeout 15 bash -c {!! $service['networkingFragment'] !!} 2>/dev/null || true)

echo "###VITO:{{ $service['id'] }}:networking###"

printf '%s\n' "$OUT"

@endif
@endif
@endforeach
