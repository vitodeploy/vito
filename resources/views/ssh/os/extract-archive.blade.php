echo "Starting archive extraction..."
echo "Archive path: {{ $backupPath }}"
echo "Extract path: {{ $restorePath }}"

# Check if archive exists
if ! test -f '{{ $backupPath }}'; then
    echo 'VITO_SSH_ERROR: Archive does not exist' && exit 1
fi

echo "Archive exists, extracting..."

# Extract to temp directory
TEMP_DIR=$(mktemp -d)
echo "Temporary extraction directory: $TEMP_DIR"

if ! tar -xzf '{{ $backupPath }}' -C "$TEMP_DIR"; then
    echo 'VITO_SSH_ERROR: Failed to extract archive' && exit 1
fi

echo "Extraction successful, checking extracted structure..."

# Remove existing file/directory at destination if it exists
if test -e '{{ $restorePath }}'; then
    echo "Removing existing file/directory at restore path..."
    rm -rf '{{ $restorePath }}'
fi

# Create the destination directory if it doesn't exist
DEST_DIR=$(dirname '{{ $restorePath }}')
echo "Destination directory: $DEST_DIR"
if ! test -d "$DEST_DIR"; then
    echo "Creating destination directory..."
    mkdir -p "$DEST_DIR"
fi

# Get the single extracted item (archive always contains exactly one item)
SINGLE_ITEM=$(ls -A "$TEMP_DIR")
echo "Extracted item: $SINGLE_ITEM"

if [ -z "$SINGLE_ITEM" ]; then
    echo 'VITO_SSH_ERROR: No items were extracted from archive' && exit 1
fi

# Move the single item to the restore path
echo "Moving extracted item to restore path..."
if ! mv "$TEMP_DIR/$SINGLE_ITEM" '{{ $restorePath }}'; then
    echo 'VITO_SSH_ERROR: Failed to move extracted item to restore path' && exit 1
fi

echo "Archive extraction completed successfully!"
echo "Extracted to: {{ $restorePath }}"