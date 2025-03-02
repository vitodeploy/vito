if ! sudo -u postgres psql -c "CREATE DATABASE \"{{ $name }}\" WITH ENCODING '{{ $charset }}'"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "Database {{ $name }} created"
