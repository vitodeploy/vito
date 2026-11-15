#!/bin/bash


FILE_PATH={!! escapeshellarg($path) !!}

@if (isset($user))
if ! sudo -u {!! escapeshellarg($user) !!} test -f "$FILE_PATH"; then
    echo "Error: File '$FILE_PATH' does not exist"
    exit 1
fi

sudo -u {!! escapeshellarg($user) !!} truncate -s 0 "$FILE_PATH"
@else
if ! sudo test -f "$FILE_PATH"; then
    echo "Error: File '$FILE_PATH' does not exist"
    exit 1
fi

PERMISSIONS=$(sudo stat -L -c "%a" "$FILE_PATH")
OWNER=$(sudo stat -L -c "%U:%G" "$FILE_PATH")

sudo truncate -s 0 "$FILE_PATH"

sudo chown "$OWNER" "$FILE_PATH"
sudo chmod "$PERMISSIONS" "$FILE_PATH"
@endif

echo "File '$FILE_PATH' cleared successfully"
