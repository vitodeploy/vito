@php
    $literal = "E'".str_replace(['\\', "'"], ['\\\\', "''"], $password)."'";
    $sql = ($managed ?? false)
        ? 'SELECT '.(($owner ?? false) ? "'CREATE ROLE \"{$username}\" WITH NOLOGIN'" : "format('CREATE ROLE \"{$username}\" WITH LOGIN PASSWORD %L', {$literal})")
            ." WHERE NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = '{$username}')\n\\gexec\n"
        : "CREATE ROLE \"{$username}\" WITH LOGIN PASSWORD {$literal};\n";
@endphp
if ! echo {{ base64_encode($sql) }} | base64 -d | sudo -u postgres psql -v ON_ERROR_STOP=1 @if ($managed ?? false)2>/dev/null @endif; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "User {{ $username }} created"
