if ! bash -c 'set -o pipefail
GZIP=$(command -v pigz || echo gzip)
if [ "$(basename "$GZIP")" = "pigz" ]; then
    echo "Compressing with pigz (parallel)"
else
    echo "Using gzip (pigz not installed, install pigz for faster parallel compression)"
fi
START=$SECONDS
sudo -u postgres pg_dump --clean --if-exists --no-owner --no-privileges -d "$1" | $GZIP > "$2"
STATUS=$?
echo "Backup completed in $((SECONDS - START))s"
exit $STATUS' _ {!! escapeshellarg($database) !!} {!! escapeshellarg($path) !!}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
