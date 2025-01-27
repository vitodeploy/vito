if ! sudo -u postgres psql -c "DROP DATABASE \"{{ $name }}\""; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "Database {{ $name }} deleted"
