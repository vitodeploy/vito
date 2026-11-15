if ! sudo -u postgres psql -c "DROP USER @if ($managed ?? false)IF EXISTS @endif\"{{ $username }}\""; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "User {{ $username }} deleted"
