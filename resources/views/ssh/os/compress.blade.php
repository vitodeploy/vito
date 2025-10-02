echo "Starting file compression..."
echo "Source path: {{ $sourcePath }}"
echo "Tar path: {{ $zipPath }}"

if ! test -e '{{ $sourcePath }}'; then
    echo 'VITO_SSH_ERROR: Source path does not exist' && exit 1
fi

echo "Source path exists, compressing with tar..."

# Use tar to create compressed archive (works for both files and directories)
if ! tar -czf '{{ $zipPath }}' '{{ $sourcePath }}'; then
    echo 'VITO_SSH_ERROR: Failed to compress with tar' && exit 1
fi

echo "Checking if tar file was created..."

if ! test -f '{{ $zipPath }}'; then
    echo 'VITO_SSH_ERROR: Tar file was not created' && exit 1
fi

echo "Checking if tar file is not empty..."

if ! test -s '{{ $zipPath }}'; then
    echo 'VITO_SSH_ERROR: Tar file is empty' && exit 1
fi

echo "File compression completed successfully!"
