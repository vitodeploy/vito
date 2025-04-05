if ! sudo mariadb -e "SELECT u.User,
       u.Host,
       (SELECT group_concat(distinct p.TABLE_SCHEMA)
        FROM information_schema.SCHEMA_PRIVILEGES p
        WHERE p.GRANTEE = concat('\'', u.User, '\'', '@', '\'', u.Host, '\'')) as Privileges
FROM mysql.user u;";
then
    echo 'VITO_SSH_ERROR' && exit 1
fi
