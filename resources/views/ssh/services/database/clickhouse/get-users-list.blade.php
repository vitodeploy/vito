if ! sudo clickhouse-client --format=TabSeparatedWithNames -q "SELECT u.name AS User, '' AS Host, if(empty(arrayStringConcat(arrayFilter(x -> isNotNull(x) and not empty(x), groupArray(distinct g.database)), ',')), 'NULL', arrayStringConcat(arrayFilter(x -> isNotNull(x) and not empty(x), groupArray(distinct g.database)), ',')) AS Privileges FROM system.users u LEFT JOIN system.grants g ON g.user_name = u.name GROUP BY u.name;"; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
