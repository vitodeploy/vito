USER_TO_REVOKE='__username__'

DATABASES=$(sudo -u postgres psql -t -c "SELECT datname FROM pg_database WHERE datistemplate = false;")

for DB in $DATABASES; do
    echo "Revoking privileges in database: $DB"
    sudo -u postgres psql -d "$DB" -c "REVOKE ALL PRIVILEGES ON DATABASE \"$DB\" FROM $USER_TO_REVOKE;"
done

echo "Privileges revoked from $USER_TO_REVOKE"
