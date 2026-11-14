@php($sql = "CREATE USER IF NOT EXISTS '{$username}'@'{$host}' IDENTIFIED BY '".addslashes($password)."';")
if ! echo {{ base64_encode($sql) }} | base64 -d | sudo mysql; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo mysql -e "FLUSH PRIVILEGES"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "Command executed"
