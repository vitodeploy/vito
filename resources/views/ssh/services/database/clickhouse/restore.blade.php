if ! bash -c 'set -euo pipefail
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

sudo clickhouse-client -q "RESTORE DATABASE \`$1\` AS \`${STAGING_DB}\` FROM Disk(\x27backups\x27, \x27${BACKUP_ID}.tar.gz\x27);"
sudo clickhouse-client -q "DROP DATABASE IF EXISTS \`$1\` SYNC;"
sudo clickhouse-client -q "RENAME DATABASE \`${STAGING_DB}\` TO \`$1\`;"' _ {!! escapeshellarg($database) !!} {!! escapeshellarg($path) !!}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

rm -f {!! escapeshellarg($path) !!}
