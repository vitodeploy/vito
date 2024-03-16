if ! sudo -u postgres psql -c "DROP DATABASE __name__"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "Database __name__ deleted"
