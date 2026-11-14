if ! sudo mysql -e "CREATE DATABASE IF NOT EXISTS \`{{ $name }}\` CHARACTER SET '{{ $charset }}' COLLATE '{{ $collation }}'"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "Command executed"
