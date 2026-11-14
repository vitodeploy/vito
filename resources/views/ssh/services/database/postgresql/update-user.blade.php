@if ($newPassword)
@php($sql = "ALTER ROLE \"{$username}\" WITH PASSWORD E'".str_replace(['\\', "'"], ['\\\\', "''"], $newPassword)."';\n")
if ! echo {{ base64_encode($sql) }} | base64 -d | sudo -u postgres psql -v ON_ERROR_STOP=1; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
@endif


echo "User {{ $username }} updated"
