echo "Starting archive extraction..."
echo "Archive path: {{ $backupPath }}"
echo "Extract path: {{ $restorePath }}"
echo "Owner: {{ $owner ?? 'vito:vito' }}"
echo "Permissions: {{ $permissions ?? '755' }}"

# Check if archive exists
if ! test -f '{{ $backupPath }}'; then
    echo 'VITO_SSH_ERROR: Archive does not exist' && exit 1
fi

echo "Archive exists, extracting..."

# Remove existing file/directory at destination if it exists (using sudo for permissions)
if test -e '{{ $restorePath }}'; then
    echo "Removing existing file/directory at restore path..."
    sudo rm -rf '{{ $restorePath }}'
fi

# Create the destination directory if it doesn't exist (using sudo for permissions)
DEST_DIR=$(dirname '{{ $restorePath }}')
echo "Destination directory: $DEST_DIR"
if ! test -d "$DEST_DIR"; then
    echo "Creating destination directory..."
    sudo mkdir -p "$DEST_DIR"
fi

# Extract to temp directory first to check contents
TEMP_DIR=$(mktemp -d)
echo "Temporary extraction directory: $TEMP_DIR"

if ! tar -xzf '{{ $backupPath }}' -C "$TEMP_DIR"; then
    echo 'VITO_SSH_ERROR: Failed to extract archive' && exit 1
fi

echo "Extraction successful, checking extracted structure..."

# Get the single extracted item (archive always contains exactly one item)
SINGLE_ITEM=$(ls -A "$TEMP_DIR")
echo "Extracted item: $SINGLE_ITEM"

if [ -z "$SINGLE_ITEM" ]; then
    echo 'VITO_SSH_ERROR: No items were extracted from archive' && exit 1
fi

# Extract directly to the restore path
echo "Extracting to restore path..."
if ! sudo tar -xzf '{{ $backupPath }}' -C "$DEST_DIR"; then
    echo 'VITO_SSH_ERROR: Failed to extract archive' && exit 1
fi

# Move the extracted item to the exact restore path if needed
EXTRACTED_PATH="$DEST_DIR/$SINGLE_ITEM"
if [ "$EXTRACTED_PATH" != '{{ $restorePath }}' ]; then
    echo "Moving extracted item to exact restore path..."
    if ! sudo mv "$EXTRACTED_PATH" '{{ $restorePath }}'; then
        echo 'VITO_SSH_ERROR: Failed to move to exact restore path' && exit 1
    fi
fi

# Set custom owner and permissions
echo "Setting owner and permissions..."
if ! sudo chown '{{ $owner ?? 'vito:vito' }}' '{{ $restorePath }}'; then
    echo 'VITO_SSH_ERROR: Failed to set owner' && exit 1
fi

if ! sudo chmod '{{ $permissions ?? '755' }}' '{{ $restorePath }}'; then
    echo 'VITO_SSH_ERROR: Failed to set permissions' && exit 1
fi

# Clean up temp directory
rm -rf "$TEMP_DIR"

echo "Archive extraction completed successfully!"
echo "Extracted to: {{ $restorePath }}"