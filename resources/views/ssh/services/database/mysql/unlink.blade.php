sudo mysql -e "REVOKE ALL PRIVILEGES, GRANT OPTION FROM '{{ $username }}'@'{{ $host }}'" 2>/dev/null || true

if ! sudo mysql -e "FLUSH PRIVILEGES"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "Command executed"
