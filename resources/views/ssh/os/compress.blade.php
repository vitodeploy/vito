echo "Starting compression..."
echo "Source path: {{ $sourcePath }}"
echo "Archive path: {{ $zipPath }}"

if ! test -e '{{ $sourcePath }}'; then
    echo 'VITO_SSH_ERROR: Source path does not exist' && exit 1
fi

echo "Source path exists, compressing with tar..."

# Get the item name and parent directory
ITEM_NAME=$(basename '{{ $sourcePath }}')
PARENT_DIR=$(dirname '{{ $sourcePath }}')

echo "Item name: $ITEM_NAME"
echo "Parent directory: $PARENT_DIR"

# Change to parent directory and compress the item (file or directory)
# This creates a tar with only one level: the item name
if ! tar -czf '{{ $zipPath }}' -C "$PARENT_DIR" "$ITEM_NAME"; then
    echo 'VITO_SSH_ERROR: Failed to compress with tar' && exit 1
fi

echo "Compression completed successfully!"

echo "Checking if archive was created..."

if ! test -f '{{ $zipPath }}'; then
    echo 'VITO_SSH_ERROR: Archive was not created' && exit 1
fi

echo "Checking if archive is not empty..."

if ! test -s '{{ $zipPath }}'; then
    echo 'VITO_SSH_ERROR: Archive is empty' && exit 1
fi

echo "Compression completed successfully!"