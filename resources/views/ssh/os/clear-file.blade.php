#!/bin/bash

# Clear remote log file while preserving permissions and ownership
# Usage: clear-file.sh <file_path>

FILE_PATH="{{ $path }}"

# Check if the file exists
if [ ! -f "$FILE_PATH" ]; then
    echo "Error: File '$FILE_PATH' does not exist"
    exit 1
fi

# Get the current permissions and ownership
PERMISSIONS=$(stat -c "%a" "$FILE_PATH")
OWNER=$(stat -c "%U:%G" "$FILE_PATH")

# Clear the file contents using sudo to ensure we have write permissions
sudo truncate -s 0 "$FILE_PATH"

# Restore the original permissions and ownership
sudo chown "$OWNER" "$FILE_PATH"
sudo chmod "$PERMISSIONS" "$FILE_PATH"

echo "File '$FILE_PATH' cleared successfully"
