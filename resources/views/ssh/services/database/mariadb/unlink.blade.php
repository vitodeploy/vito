if ! sudo mysql -e "REVOKE ALL PRIVILEGES, GRANT OPTION FROM '{{ $username }}'@'{{ $host }}'"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "Command executed"
