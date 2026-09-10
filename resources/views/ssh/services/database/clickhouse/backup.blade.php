if ! bash -c 'set -euo pipefail
SQ=$(printf "\x27")
BACKUP_ID="backup_$(date +%s%N)"
ARCHIVE="/var/lib/clickhouse/backups/${BACKUP_ID}.tar.gz"

cleanup() {
    sudo rm -f "$ARCHIVE"
}
trap cleanup EXIT

sudo mkdir -p /var/lib/clickhouse/backups
sudo chown clickhouse:clickhouse /var/lib/clickhouse/backups

DB_NAME="${1//\\/\\\\}"
DB_NAME="${DB_NAME//\`/\`\`}"

sudo clickhouse-client -q "BACKUP DATABASE \`${DB_NAME}\` TO Disk(${SQ}backups${SQ}, ${SQ}${BACKUP_ID}.tar.gz${SQ}) SETTINGS compression_method=${SQ}gzip${SQ};"

sudo mv "$ARCHIVE" "$2"
sudo chown "$(id -un):$(id -gn)" "$2"' _ {!! escapeshellarg($database) !!} {!! escapeshellarg($path) !!}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
