@if ($newPassword)
if ! sudo -u postgres psql -v ON_ERROR_STOP=1 -v password="$(echo {{ base64_encode($newPassword) }} | base64 -d)" <<'VITO_UPDATE_USER'
ALTER ROLE "{{ $username }}" WITH PASSWORD :'password';
VITO_UPDATE_USER
then
    echo 'VITO_SSH_ERROR' && exit 1
fi
@endif


echo "User {{ $username }} updated"
