if ! sudo mysql -e "GRANT ALL PRIVILEGES ON __database__.* TO '__username__'@'__host__'"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo mysql -e "FLUSH PRIVILEGES"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "Linking to __database__ finished"
