echo "Host __host__-__key__
        Hostname __host__
        IdentityFile=~/.ssh/__key__" >> ~/.ssh/config

ssh-keyscan -H __host__ >> ~/.ssh/known_hosts

rm -rf __path__

if ! git config --global core.fileMode false; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! git clone -b __branch__ __repo__ __path__; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! find __path__ -type d -exec chmod 755 {} \;; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! find __path__ -type f -exec chmod 644 {} \;; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! cd __path__ && git config core.fileMode false; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
