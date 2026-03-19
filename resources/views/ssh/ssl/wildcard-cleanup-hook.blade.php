#!/bin/bash
N=$(ls -1 {!! $basePath !!}/cleanup-signal-* 2>/dev/null | wc -l)
N=$((N + 1))
echo "${CERTBOT_DOMAIN}|${CERTBOT_VALIDATION}" > {!! $basePath !!}/cleanup-signal-${N}.tmp
mv {!! $basePath !!}/cleanup-signal-${N}.tmp {!! $basePath !!}/cleanup-signal-${N}
for i in $(seq 1 120); do
  [ -f "{!! $basePath !!}/cleanup-done-${N}" ] && exit 0
  sleep 1
done
exit 1
