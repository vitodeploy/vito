USER_TO_LINK='{{ $username }}'
DB_NAME='{{ $database }}'
DB_VERSION='{{ $version }}'
PERMISSION='{{ $permission ?? 'admin' }}'

# Revoke all existing privileges first to ensure clean state
# Revoke privileges on existing objects
sudo -u postgres psql -d "$DB_NAME" -c "REVOKE ALL PRIVILEGES ON ALL TABLES IN SCHEMA public FROM $USER_TO_LINK CASCADE;" 2>/dev/null || true
sudo -u postgres psql -d "$DB_NAME" -c "REVOKE ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public FROM $USER_TO_LINK CASCADE;" 2>/dev/null || true
sudo -u postgres psql -d "$DB_NAME" -c "REVOKE ALL PRIVILEGES ON ALL FUNCTIONS IN SCHEMA public FROM $USER_TO_LINK CASCADE;" 2>/dev/null || true
sudo -u postgres psql -c "REVOKE ALL PRIVILEGES ON DATABASE \"$DB_NAME\" FROM $USER_TO_LINK CASCADE;" 2>/dev/null || true
sudo -u postgres psql -d "$DB_NAME" -c "REVOKE ALL ON SCHEMA public FROM $USER_TO_LINK CASCADE;" 2>/dev/null || true

# Revoke default privileges from postgres user for this user
sudo -u postgres psql -d "$DB_NAME" -c "ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public REVOKE ALL ON TABLES FROM $USER_TO_LINK;" 2>/dev/null || true
sudo -u postgres psql -d "$DB_NAME" -c "ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public REVOKE ALL ON SEQUENCES FROM $USER_TO_LINK;" 2>/dev/null || true
sudo -u postgres psql -d "$DB_NAME" -c "ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public REVOKE ALL ON FUNCTIONS FROM $USER_TO_LINK;" 2>/dev/null || true

# Revoke default privileges from all other roles (get database owner and revoke from them)
DB_OWNER=$(sudo -u postgres psql -d "$DB_NAME" -tAc "SELECT pg_catalog.pg_get_userbyid(d.datdba) FROM pg_catalog.pg_database d WHERE d.datname = '$DB_NAME';")
if [ "$DB_OWNER" != "$USER_TO_LINK" ] && [ -n "$DB_OWNER" ]; then
    sudo -u postgres psql -d "$DB_NAME" -c "ALTER DEFAULT PRIVILEGES FOR ROLE $DB_OWNER IN SCHEMA public REVOKE ALL ON TABLES FROM $USER_TO_LINK;" 2>/dev/null || true
    sudo -u postgres psql -d "$DB_NAME" -c "ALTER DEFAULT PRIVILEGES FOR ROLE $DB_OWNER IN SCHEMA public REVOKE ALL ON SEQUENCES FROM $USER_TO_LINK;" 2>/dev/null || true
    sudo -u postgres psql -d "$DB_NAME" -c "ALTER DEFAULT PRIVILEGES FOR ROLE $DB_OWNER IN SCHEMA public REVOKE ALL ON FUNCTIONS FROM $USER_TO_LINK;" 2>/dev/null || true
fi

# Also revoke any default privileges set by this user themselves
sudo -u postgres psql -d "$DB_NAME" -c "ALTER DEFAULT PRIVILEGES FOR ROLE $USER_TO_LINK IN SCHEMA public REVOKE ALL ON TABLES FROM $USER_TO_LINK;" 2>/dev/null || true
sudo -u postgres psql -d "$DB_NAME" -c "ALTER DEFAULT PRIVILEGES FOR ROLE $USER_TO_LINK IN SCHEMA public REVOKE ALL ON SEQUENCES FROM $USER_TO_LINK;" 2>/dev/null || true
sudo -u postgres psql -d "$DB_NAME" -c "ALTER DEFAULT PRIVILEGES FOR ROLE $USER_TO_LINK IN SCHEMA public REVOKE ALL ON FUNCTIONS FROM $USER_TO_LINK;" 2>/dev/null || true

# Grant appropriate privileges based on permission level
if [ "$PERMISSION" = "read" ]; then
    # Read-only access
    if ! sudo -u postgres psql -c "GRANT CONNECT ON DATABASE \"$DB_NAME\" TO $USER_TO_LINK;"; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi
    if ! sudo -u postgres psql -d "$DB_NAME" -c "GRANT USAGE ON SCHEMA public TO $USER_TO_LINK;"; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi
    if ! sudo -u postgres psql -d "$DB_NAME" -c "GRANT SELECT ON ALL TABLES IN SCHEMA public TO $USER_TO_LINK;"; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi
    if ! sudo -u postgres psql -d "$DB_NAME" -c "GRANT SELECT ON ALL SEQUENCES IN SCHEMA public TO $USER_TO_LINK;"; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi
    if ! sudo -u postgres psql -d "$DB_NAME" -c "ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT SELECT ON TABLES TO $USER_TO_LINK;"; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi
elif [ "$PERMISSION" = "write" ]; then
    # Write access (no DROP or TRUNCATE)
    if ! sudo -u postgres psql -c "GRANT CONNECT ON DATABASE \"$DB_NAME\" TO $USER_TO_LINK;"; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi
    if ! sudo -u postgres psql -d "$DB_NAME" -c "GRANT USAGE ON SCHEMA public TO $USER_TO_LINK;"; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi
    if [ "$DB_VERSION" -ge 15 ]; then
        if ! sudo -u postgres psql -d "$DB_NAME" -c "GRANT CREATE ON SCHEMA public TO $USER_TO_LINK;"; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi
    fi
    if ! sudo -u postgres psql -d "$DB_NAME" -c "GRANT SELECT, INSERT, UPDATE, DELETE, REFERENCES, TRIGGER ON ALL TABLES IN SCHEMA public TO $USER_TO_LINK;"; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi
    if ! sudo -u postgres psql -d "$DB_NAME" -c "GRANT USAGE, SELECT, UPDATE ON ALL SEQUENCES IN SCHEMA public TO $USER_TO_LINK;"; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi
    if ! sudo -u postgres psql -d "$DB_NAME" -c "GRANT EXECUTE ON ALL FUNCTIONS IN SCHEMA public TO $USER_TO_LINK;"; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi
    if ! sudo -u postgres psql -d "$DB_NAME" -c "ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT SELECT, INSERT, UPDATE, DELETE, REFERENCES, TRIGGER ON TABLES TO $USER_TO_LINK;"; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi
    if ! sudo -u postgres psql -d "$DB_NAME" -c "ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT USAGE, SELECT, UPDATE ON SEQUENCES TO $USER_TO_LINK;"; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi
else
    # Admin access (all privileges)
    if ! sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE \"$DB_NAME\" TO $USER_TO_LINK;"; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi
    if [ "$DB_VERSION" -ge 15 ]; then
        if ! sudo -u postgres psql -d "$DB_NAME" -c "GRANT USAGE, CREATE ON SCHEMA public TO $USER_TO_LINK;"; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi
    fi
    if ! sudo -u postgres psql -d "$DB_NAME" -c "GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO $USER_TO_LINK;"; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi
    if ! sudo -u postgres psql -d "$DB_NAME" -c "GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO $USER_TO_LINK;"; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi
    if ! sudo -u postgres psql -d "$DB_NAME" -c "ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL PRIVILEGES ON TABLES TO $USER_TO_LINK;"; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi
    if ! sudo -u postgres psql -d "$DB_NAME" -c "ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL PRIVILEGES ON SEQUENCES TO $USER_TO_LINK;"; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi
fi

echo "Linking to $DB_NAME finished"
