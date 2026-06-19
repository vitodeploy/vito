echo "Starting compression..."
echo "Source path: {{ $sourcePath }}"
echo "Archive path: {{ $zipPath }}"

if ! test -e '{{ $sourcePath }}'; then
    echo 'VITO_SSH_ERROR: Source path does not exist' && exit 1
fi

# Get the item name and parent directory
ITEM_NAME=$(basename '{{ $sourcePath }}')
PARENT_DIR=$(dirname '{{ $sourcePath }}')

echo "Item name: $ITEM_NAME"
echo "Parent directory: $PARENT_DIR"

# Use pigz for parallel compression when available, otherwise fall back to gzip
GZIP=$(command -v pigz || echo gzip)
if [ "$(basename "$GZIP")" = "pigz" ]; then
    echo "Compressing with tar + pigz (parallel)"
else
    echo "Compressing with tar + gzip (pigz not installed, install pigz for faster parallel compression)"
fi

# Change to parent directory and compress the item (file or directory)
# This creates a tar with only one level: the item name
START=$SECONDS
if ! tar -I "$GZIP" -cf '{{ $zipPath }}' -C "$PARENT_DIR" "$ITEM_NAME"; then
    echo 'VITO_SSH_ERROR: Failed to compress with tar' && exit 1
fi

echo "Compression completed in $((SECONDS - START))s"

echo "Checking if archive was created..."

if ! test -f '{{ $zipPath }}'; then
    echo 'VITO_SSH_ERROR: Archive was not created' && exit 1
fi

echo "Checking if archive is not empty..."

if ! test -s '{{ $zipPath }}'; then
    echo 'VITO_SSH_ERROR: Archive is empty' && exit 1
fi

echo "Compression completed successfully!"