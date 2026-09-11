if ! sudo clickhouse-client --format=TabSeparatedWithNames -q "SELECT name AS database_name, 'UTF8' AS charset, 'utf8' AS collation FROM system.databases;"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
