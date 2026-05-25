if ! sudo supervisorctl reread; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
@foreach ($ids as $id)
if ! sudo supervisorctl update {{ (int) $id }}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
@endforeach
output=$(sudo supervisorctl restart {{ collect($ids)->map(fn ($id) => (int) $id.':*')->implode(' ') }} 2>&1) || true
echo "$output"
