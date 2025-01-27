USER_TO_LINK='{{ $username }}'
DB_NAME='{{ $database }}'
DB_VERSION='{{ $version }}'

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
