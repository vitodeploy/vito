if ! sudo mariadb -e "SHOW COLLATION;";
then
    echo 'VITO_SSH_ERROR' && exit 1
fi
