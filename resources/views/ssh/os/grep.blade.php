sudo grep -F -i -- {!! escapeshellarg($term) !!} {!! escapeshellarg($path) !!} 2>/dev/null | tail -n {{ (int) $lines }}
