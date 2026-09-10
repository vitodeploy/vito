if ! bash -c 'set -o pipefail
BACKUP_ID="restore_$(date +%s%N)"
sudo mkdir -p /var/lib/clickhouse/backups
sudo cp "$2" "/var/lib/clickhouse/backups/${BACKUP_ID}.tar.gz"
sudo chown clickhouse:clickhouse "/var/lib/clickhouse/backups/${BACKUP_ID}.tar.gz"

sudo clickhouse-client -q "DROP DATABASE IF EXISTS \`$1\` SYNC;"
sudo clickhouse-client -q "RESTORE DATABASE \`$1\` FROM Disk(\x27backups\x27, \x27${BACKUP_ID}.tar.gz\x27);"
STATUS=$?
sudo rm -f "/var/lib/clickhouse/backups/${BACKUP_ID}.tar.gz"
exit $STATUS' _ {!! escapeshellarg($database) !!} {!! escapeshellarg($path) !!}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

rm -f {!! escapeshellarg($path) !!}
