if ! sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE __database__ TO __username__;"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "Linking to __database__ finished"
