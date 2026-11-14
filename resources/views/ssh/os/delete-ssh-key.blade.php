if [ -f ~/.ssh/authorized_keys ]; then
    if ! awk -v key={!! escapeshellarg($material) !!} '{ for (i = 1; i <= NF; i++) if ($i == key) next } { print }' ~/.ssh/authorized_keys > ~/.ssh/authorized_keys.tmp; then
        rm -f ~/.ssh/authorized_keys.tmp
        echo 'VITO_SSH_ERROR' && exit 1
    fi
    if grep -qF -- {!! escapeshellarg($material) !!} ~/.ssh/authorized_keys.tmp; then
        rm -f ~/.ssh/authorized_keys.tmp
        echo 'VITO_SSH_ERROR' && exit 1
    fi
    cat ~/.ssh/authorized_keys.tmp > ~/.ssh/authorized_keys && rm -f ~/.ssh/authorized_keys.tmp
    chmod 600 ~/.ssh/authorized_keys
fi
