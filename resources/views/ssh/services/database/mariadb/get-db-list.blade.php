if ! sudo mariadb -e "SELECT
    SCHEMA_NAME AS database_name,
    DEFAULT_CHARACTER_SET_NAME AS charset,
    DEFAULT_COLLATION_NAME AS collation
    FROM information_schema.SCHEMATA;";
then
    echo 'VITO_SSH_ERROR' && exit 1
fi


