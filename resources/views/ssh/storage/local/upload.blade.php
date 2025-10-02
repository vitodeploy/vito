echo "Starting local storage upload..."
echo "Source file: {{ $src }}"
echo "Destination directory: {{ $destDir }}"
echo "Destination file: {{ $destFile }}"

echo "Creating destination directory..."
mkdir -p {{ $destDir }}

echo "Checking if source file exists..."
if ! test -f '{{ $src }}'; then
    echo 'VITO_SSH_ERROR: Source file does not exist' && exit 1
fi

echo "Copying file to destination..."
cp {{ $src }} {{ $destFile }}

echo "Checking if destination file was created..."
if ! test -f '{{ $destFile }}'; then
    echo 'VITO_SSH_ERROR: Destination file was not created' && exit 1
fi

echo "Local storage upload completed successfully!"
