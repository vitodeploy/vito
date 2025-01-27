USER_TO_REVOKE='{{ $username }}'
DB_VERSION='{{ $version }}'

DATABASES=$(sudo -u postgres psql -t -c "SELECT datname FROM pg_database WHERE datistemplate = false;")

for DB in $DATABASES; do
    echo "Revoking privileges in database: $DB"
    sudo -u postgres psql -d "$DB" -c "REVOKE ALL PRIVILEGES ON DATABASE \"$DB\" FROM $USER_TO_REVOKE;"

    # Check if PostgreSQL version is 15 or greater
    if [ "$DB_VERSION" -ge 15 ]; then
        sudo -u postgres psql -d "$DB" -c "REVOKE USAGE, CREATE ON SCHEMA public FROM $USER_TO_REVOKE;"
    fi
done

echo "Privileges revoked from $USER_TO_REVOKE"
