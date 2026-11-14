@php($sql = "CREATE USER IF NOT EXISTS '{$username}'@'{$host}' IDENTIFIED BY '".addslashes($password)."'")
if ! sudo mysql -e "$(echo {{ base64_encode($sql) }} | base64 -d)"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo mysql -e "FLUSH PRIVILEGES"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "Command executed"
