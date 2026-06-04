#!/usr/bin/env bash
# vito-goaccess-script-version: {{ $scriptVersion }}
set -euo pipefail

CONF="${1:-}"
[ -n "$CONF" ] && [ -f "$CONF" ] || exit 0

# shellcheck disable=SC1090
source "$CONF"

BASE_DIR="{{ $baseDir }}"
SITE_DATA="$BASE_DIR/data/$SITE_ID"
mkdir -p "$SITE_DATA"

# Per-site lock shared by the hourly runner AND the manual-refresh path.
# Non-blocking: if a run is already in progress, report busy and exit cleanly
# (exit 0 so the SSH `set -e` wrapper does not treat it as a failure).
exec 9>"$SITE_DATA/.lock"
if ! flock -n 9; then
    echo "VITO_STATS_BUSY"
    exit 0
fi

STARTED_AT="$(date -u +%FT%TZ)"
THIS_MONTH="$(date +%Y-%m)"
DAY="$((10#$(date +%d)))"

MONTHS=("$THIS_MONTH")
BOUNDARY=0
if [ "$DAY" -le 2 ]; then
    BOUNDARY=1
    MONTHS+=("$(date -d "$THIS_MONTH-01 -1 day" +%Y-%m)")
fi

process_month() {
    local key="$1"
    local db="$SITE_DATA/$key"
    local out="$db/report.json"
    mkdir -p "$db"

    if [ ! -e "$LIVE_LOG" ]; then
        echo '{}' > "$out"
        return 0
    fi

    if [ "$BOUNDARY" = "1" ]; then
        # Boundary window (days 1–2): date-filter live+rotated into the right month db.
        if [ "$LOG_FORMAT" = "CADDY" ]; then
            # shellcheck disable=SC2086
            zcat -f $LOG_GLOB 2>/dev/null \
                | jq -c "select(.ts | startswith(\"$key\"))" \
                | goaccess - --log-format=CADDY --persist --restore --db-path="$db" -o "$out" --date-spec=date --no-progress
        else
            local mon year
            mon="$(date -d "$key-01" +%b)"
            year="$(date -d "$key-01" +%Y)"
            # shellcheck disable=SC2086
            zcat -f $LOG_GLOB 2>/dev/null \
                | grep -- "/$mon/$year:" \
                | goaccess - --log-format=COMBINED --persist --restore --db-path="$db" -o "$out" --date-spec=date --no-progress
        fi
    else
        # Normal path: direct-file incremental (GoAccess tracks inode/offset).
        # Feed only uncompressed logs — GoAccess reads rotated *.gz files as
        # binary and aborts the run. Compressed rotations were already ingested
        # by earlier runs, and the boundary window above re-reads them via
        # `zcat -f` when crossing months.
        local files=() f
        # shellcheck disable=SC2086
        for f in $LOG_GLOB; do
            [ -e "$f" ] || continue
            case "$f" in *.gz) continue ;; esac
            files+=("$f")
        done
        [ "${#files[@]}" -gt 0 ] || return 0
        goaccess "${files[@]}" --log-format="$LOG_FORMAT" --persist --restore --db-path="$db" -o "$out" --date-spec=date --no-progress
    fi
}

build_summary() {
    local summary="$SITE_DATA/summary.json"
    local entries=""
    for d in "$SITE_DATA"/*/; do
        local key
        key="$(basename "$d")"
        [[ "$key" =~ ^[0-9]{4}-[0-9]{2}$ ]] || continue
        [ -f "$d/report.json" ] || continue
        local v h b
        v="$(jq -r '(.general.unique_visitors // 0) | tonumber? // 0' "$d/report.json" 2>/dev/null || echo 0)"
        h="$(jq -r '(.general.total_requests // 0) | tonumber? // 0' "$d/report.json" 2>/dev/null || echo 0)"
        b="$(jq -r '(.general.bandwidth // 0) | tonumber? // 0' "$d/report.json" 2>/dev/null || echo 0)"
        [ -n "$entries" ] && entries="$entries,"
        entries="$entries{\"month\":\"$key\",\"visitors\":$v,\"hits\":$h,\"bandwidth\":$b}"
    done
    printf '[%s]\n' "$entries" > "$summary"
}

prune() {
    local cutoff
    cutoff="$(date -d "$THIS_MONTH-01 -$((RETENTION_MONTHS - 1)) month" +%Y-%m 2>/dev/null || true)"
    [ -n "$cutoff" ] || return 0
    for d in "$SITE_DATA"/*/; do
        local key
        key="$(basename "$d")"
        [[ "$key" =~ ^[0-9]{4}-[0-9]{2}$ ]] || continue
        if [[ "$key" < "$cutoff" ]]; then
            rm -rf "$d"
        fi
    done
}

write_status() {
    local exit_code="$1"
    local error_json="$2"
    local success_at="$3"
    local months_json="$4"
    cat > "$SITE_DATA/status.json" <<JSON
{"schema":1,"site_id":$SITE_ID,"last_run_started_at":"$STARTED_AT","last_run_finished_at":"$(date -u +%FT%TZ)","last_success_at":$success_at,"exit_code":$exit_code,"error":$error_json,"months_processed":[$months_json]}
JSON
}

trap 'rc=$?; write_status "$rc" "\"processing failed\"" "null" ""; chown -R "$SSH_USER:$SSH_USER" "$SITE_DATA" 2>/dev/null || true' ERR

processed=""
for m in "${MONTHS[@]}"; do
    process_month "$m"
    [ -n "$processed" ] && processed="$processed,"
    processed="$processed\"$m\""
done

build_summary
prune
write_status 0 "null" "\"$(date -u +%FT%TZ)\"" "$processed"
chown -R "$SSH_USER:$SSH_USER" "$SITE_DATA" 2>/dev/null || true
