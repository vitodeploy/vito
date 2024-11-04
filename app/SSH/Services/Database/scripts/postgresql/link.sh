USER_TO_LINK='__username__'
DB_NAME='__database__'
DB_VERSION='__version__'

if ! sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE \"$DB_NAME\" TO $USER_TO_LINK;"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

# Check if PostgreSQL version is 15 or greater
if [ "$DB_VERSION" -ge 15 ]; then
    if ! sudo -u postgres psql -d "$DB_NAME" -c "GRANT USAGE, CREATE ON SCHEMA public TO $USER_TO_LINK;"; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi
fi

echo "Linking to $DB_NAME finished"
