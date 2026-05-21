sudo tail -n 100000 {!! escapeshellarg($path) !!} 2>/dev/null | grep -F -i -- {!! escapeshellarg($term) !!} | tail -n {{ (int) $lines }}
