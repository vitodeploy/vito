
if ! sudo -u postgres psql -c "CREATE DATABASE __name__"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "Database __name__ created"
