NS=$(dig +short NS {!! $domain !!} | head -1)
if [ -z "$NS" ]; then
  PARENT=$(echo {!! $domain !!} | sed 's/^[^.]*\.//')
  NS=$(dig +short NS $PARENT | head -1)
fi
if [ -z "$NS" ]; then
  NS="1.1.1.1"
fi
dig +short A {!! $domain !!} @$NS
dig +short AAAA {!! $domain !!} @$NS
