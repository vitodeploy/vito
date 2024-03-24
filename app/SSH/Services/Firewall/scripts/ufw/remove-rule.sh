if ! sudo ufw delete __type__ from __source____mask__ to any proto __protocol__ port __port__; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo ufw reload; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! sudo service ufw restart; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
