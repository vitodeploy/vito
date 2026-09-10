if ! bash -c 'set -euo pipefail
SQ=$(printf "\x27")
BACKUP_ID="restore_$(date +%s%N)"
STAGING_DB="_vito_restore_$(date +%s%N)"
ARCHIVE="/var/lib/clickhouse/backups/${BACKUP_ID}.tar.gz"

cleanup() {
    sudo rm -f "$ARCHIVE"
    sudo clickhouse-client -q "DROP DATABASE IF EXISTS \`${STAGING_DB}\` SYNC;" 2>/dev/null || true
}
trap cleanup EXIT

sudo mkdir -p /var/lib/clickhouse/backups
sudo cp "$2" "$ARCHIVE"
sudo chown clickhouse:clickhouse "$ARCHIVE"

DB_NAME="${1//\\/\\\\}"
DB_NAME="${DB_NAME//\`/\`\`}"

sudo clickhouse-client -q "RESTORE DATABASE \`${DB_NAME}\` AS \`${STAGING_DB}\` FROM Disk(${SQ}backups${SQ}, ${SQ}${BACKUP_ID}.tar.gz${SQ});"
sudo clickhouse-client -q "DROP DATABASE IF EXISTS \`${DB_NAME}\` SYNC;"
sudo clickhouse-client -q "RENAME DATABASE \`${STAGING_DB}\` TO \`${DB_NAME}\`;"' _ {!! escapeshellarg($database) !!} {!! escapeshellarg($path) !!}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

rm -f {!! escapeshellarg($path) !!}
