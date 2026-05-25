if ! sudo mkdir -p {{ $path }}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! echo "{{ $certificate }}" | sudo tee {{ $certificatePath }} > /dev/null; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! echo "{{ $pk }}" | sudo tee {{ $pkPath }} > /dev/null; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "Successfully received certificate"
