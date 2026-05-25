@if(! empty($search))
sudo journalctl -u {!! escapeshellarg($unit) !!} --since '7 days ago' --no-pager --output=short-iso 2>/dev/null | grep -F -i -- {!! escapeshellarg($search) !!} | tail -n {{ (int) $lines }}
@else
sudo journalctl -u {!! escapeshellarg($unit) !!} -n {{ (int) $lines }} --no-pager --output=short-iso 2>/dev/null
@endif
