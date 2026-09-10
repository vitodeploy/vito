if ! bash -c 'set -euo pipefail
BACKUP_ID="backup_$(date +%s%N)"
ARCHIVE="/var/lib/clickhouse/backups/${BACKUP_ID}.tar.gz"

cleanup() {
    sudo rm -f "$ARCHIVE"
}
trap cleanup EXIT

sudo mkdir -p /var/lib/clickhouse/backups
sudo chown clickhouse:clickhouse /var/lib/clickhouse/backups

sudo clickhouse-client -q "BACKUP DATABASE \`$1\` TO Disk(\x27backups\x27, \x27${BACKUP_ID}.tar.gz\x27) SETTINGS compression_method=\x27gzip\x27;"

sudo mv "$ARCHIVE" "$2"
sudo chown $(id -un):$(id -gn) "$2"' _ {!! escapeshellarg($database) !!} {!! escapeshellarg($path) !!}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
