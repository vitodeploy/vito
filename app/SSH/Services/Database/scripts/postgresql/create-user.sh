if ! sudo -u postgres psql -c "CREATE ROLE __username__ WITH LOGIN PASSWORD '__password__';"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "User __username__ created"
