@php($sql = "CREATE USER IF NOT EXISTS '{$username}'@'{$host}' IDENTIFIED BY '".addslashes($password)."'")
if ! sudo mariadb -e {!! escapeshellarg($sql) !!}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo mariadb -e "FLUSH PRIVILEGES"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "Command executed"
