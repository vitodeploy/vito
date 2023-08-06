if ! sudo mysql -e "CREATE USER IF NOT EXISTS '__username__'@'__host__' IDENTIFIED BY '__password__'"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo mysql -e "FLUSH PRIVILEGES"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "Command executed"
