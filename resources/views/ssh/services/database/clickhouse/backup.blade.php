if ! bash -c 'set -o pipefail
BACKUP_ID="backup_$(date +%s%N)"
sudo mkdir -p /var/lib/clickhouse/backups
sudo chown clickhouse:clickhouse /var/lib/clickhouse/backups

sudo clickhouse-client -q "BACKUP DATABASE \`$1\` TO Disk(\x27backups\x27, \x27${BACKUP_ID}.tar.gz\x27) SETTINGS compression_method=\x27gzip\x27;"
STATUS=$?
if [ $STATUS -eq 0 ]; then
    sudo mv "/var/lib/clickhouse/backups/${BACKUP_ID}.tar.gz" "$2"
    sudo chown $(id -un):$(id -gn) "$2"
else
    sudo rm -f "/var/lib/clickhouse/backups/${BACKUP_ID}.tar.gz"
fi
exit $STATUS' _ {!! escapeshellarg($database) !!} {!! escapeshellarg($path) !!}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
