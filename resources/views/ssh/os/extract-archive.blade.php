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
echo "Contents of temp directory:"
ls -la "$TEMP_DIR"

# Remove existing file/directory at destination if it exists
if test -e '{{ $restorePath }}'; then
    echo "Removing existing file/directory at extract path..."
    rm -rf '{{ $restorePath }}'
fi

# Create the destination directory if it doesn't exist
DEST_DIR=$(dirname '{{ $restorePath }}')
echo "Destination directory: $DEST_DIR"
if ! test -d "$DEST_DIR"; then
    echo "Creating destination directory..."
    mkdir -p "$DEST_DIR"
fi

echo "Moving extracted content to destination..."

# Check what was extracted and handle accordingly
EXTRACTED_ITEMS=$(ls -A "$TEMP_DIR")
echo "Extracted items: $EXTRACTED_ITEMS"

if [ -z "$EXTRACTED_ITEMS" ]; then
    echo 'VITO_SSH_ERROR: No items were extracted from archive' && exit 1
fi

# Count extracted items
ITEM_COUNT=$(ls -A "$TEMP_DIR" | wc -l)
echo "Number of extracted items: $ITEM_COUNT"

if [ "$ITEM_COUNT" -eq 1 ]; then
    # Single item extracted
    SINGLE_ITEM=$(ls -A "$TEMP_DIR")
    echo "Single item: $SINGLE_ITEM"
    
    if test -f "$TEMP_DIR/$SINGLE_ITEM"; then
        # It's a file - move it directly to the restore path
        echo "Moving single file to restore path..."
        if ! mv "$TEMP_DIR/$SINGLE_ITEM" '{{ $restorePath }}'; then
            echo 'VITO_SSH_ERROR: Failed to move single file to extract path' && exit 1
        fi
    elif test -d "$TEMP_DIR/$SINGLE_ITEM"; then
        # It's a directory - move its contents to the restore path
        echo "Moving directory contents to restore path..."
        if ! mv "$TEMP_DIR/$SINGLE_ITEM"/* '{{ $restorePath }}' 2>/dev/null; then
            echo 'VITO_SSH_ERROR: Failed to move directory contents to extract path' && exit 1
        fi
    fi
else
    # Multiple items - move all to restore path
    echo "Moving multiple items to restore path..."
    if ! mv "$TEMP_DIR"/* '{{ $restorePath }}'; then
        echo 'VITO_SSH_ERROR: Failed to move multiple items to extract path' && exit 1
    fi
fi

echo "Checking if extraction was successful..."

# Verify the extract path exists after extraction
if ! test -e '{{ $restorePath }}'; then
    echo 'VITO_SSH_ERROR: Extract path does not exist after extraction' && exit 1
fi

echo "Archive extraction completed successfully!"
echo "Extracted to: {{ $restorePath }}"