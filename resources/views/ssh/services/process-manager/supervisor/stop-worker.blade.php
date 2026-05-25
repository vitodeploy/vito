if ! output=$(sudo supervisorctl stop {{ $id }}:* 2>&1); then
    echo "$output"
    echo 'VITO_SSH_ERROR' && exit 1
fi
echo "$output"
if echo "$output" | grep ': ERROR' | grep -qvE 'not running'; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
