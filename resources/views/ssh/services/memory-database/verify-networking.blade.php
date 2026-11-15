{{ $probe }}
if sudo ss -H -ltn6 'sport = :6379' | grep -Eq '(\[::\]|\*):6379[[:space:]]'; then
    printf '\nVITO_IPV6_LISTENER\n'
fi
