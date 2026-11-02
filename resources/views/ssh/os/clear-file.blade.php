#!/bin/bash

# Clear remote log file while preserving permissions and ownership
# Usage: clear-file.sh <file_path>

FILE_PATH={!! escapeshellarg($path) !!}

@if (isset($user))
# Files under a user's home are cleared as that user, so planted symlinks cannot redirect root
if ! sudo -u {!! escapeshellarg($user) !!} test -f "$FILE_PATH"; then
    echo "Error: File '$FILE_PATH' does not exist"
    exit 1
fi

sudo -u {!! escapeshellarg($user) !!} truncate -s 0 "$FILE_PATH"
@else
# Check if the file exists
if ! sudo test -f "$FILE_PATH"; then
    echo "Error: File '$FILE_PATH' does not exist"
    exit 1
fi

# Get the current permissions and ownership of the file itself
PERMISSIONS=$(sudo stat -L -c "%a" "$FILE_PATH")
OWNER=$(sudo stat -L -c "%U:%G" "$FILE_PATH")

# Clear the file contents using sudo to ensure we have write permissions
sudo truncate -s 0 "$FILE_PATH"

# Restore the original permissions and ownership
sudo chown "$OWNER" "$FILE_PATH"
sudo chmod "$PERMISSIONS" "$FILE_PATH"
@endif

echo "File '$FILE_PATH' cleared successfully"
