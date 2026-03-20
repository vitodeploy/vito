RESOLVED=""
for RESOLVER in 1.1.1.1 8.8.8.8 9.9.9.9; do
  RESOLVED="$RESOLVED
$(dig +short +time=3 +tries=2 A {!! $domain !!} @$RESOLVER 2>/dev/null || true)"
  RESOLVED="$RESOLVED
$(dig +short +time=3 +tries=2 AAAA {!! $domain !!} @$RESOLVER 2>/dev/null || true)"
done
echo "$RESOLVED" | grep -v '^$' | sort -u
