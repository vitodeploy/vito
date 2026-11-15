head -c {{ (int) $limit + 1 }} ~/.ssh/{{ $name }}.pub
