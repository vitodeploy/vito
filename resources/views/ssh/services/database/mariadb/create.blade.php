if ! sudo mysql -e "CREATE DATABASE IF NOT EXISTS {{ $name }} CHARACTER SET utf8 COLLATE utf8_general_ci"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "Command executed"
