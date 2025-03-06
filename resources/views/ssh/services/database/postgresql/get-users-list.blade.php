if ! sudo -u postgres psql -c "SELECT r.rolname                  AS username,
       ''                         as host,
       STRING_AGG(d.datname, ',') AS databases
FROM pg_roles r
         JOIN
     pg_database d ON has_database_privilege(r.rolname, d.datname, 'CONNECT')
WHERE r.rolcanlogin
GROUP BY r.rolname
ORDER BY r.rolname;";
then
    echo 'VITO_SSH_ERROR' && exit 1
fi
