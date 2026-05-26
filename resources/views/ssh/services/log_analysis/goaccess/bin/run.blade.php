#!/usr/bin/env bash
set -euo pipefail

BASE_DIR="{{ $baseDir }}"
SITES_DIR="$BASE_DIR/sites"

[ -d "$SITES_DIR" ] || exit 0

for conf in "$SITES_DIR"/*.conf; do
    [ -e "$conf" ] || continue
    # Isolate each site: a slow/hung/failed site must not starve the rest.
    timeout 300 bash "$BASE_DIR/bin/process.sh" "$conf" >/dev/null 2>&1 || true
done
