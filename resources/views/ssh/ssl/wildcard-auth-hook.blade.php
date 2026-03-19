#!/bin/bash
N=$(ls -1 {!! $basePath !!}/auth-signal-* 2>/dev/null | wc -l)
N=$((N + 1))
echo "${CERTBOT_DOMAIN}|${CERTBOT_VALIDATION}" > {!! $basePath !!}/auth-signal-${N}.tmp
mv {!! $basePath !!}/auth-signal-${N}.tmp {!! $basePath !!}/auth-signal-${N}
for i in $(seq 1 120); do
  [ -f "{!! $basePath !!}/auth-proceed-${N}" ] && exit 0
  sleep 1
done
exit 1
