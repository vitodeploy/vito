if ! sudo mysql -e "CREATE DATABASE IF NOT EXISTS __name__ CHARACTER SET utf8 COLLATE utf8_general_ci"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "Command executed"
