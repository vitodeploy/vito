mkdir -p ~/.ssh
exec 9> ~/.ssh/.vito-authorized-keys.lock
flock 9
if [ -f ~/.ssh/authorized_keys ]; then
    VITO_KEY_MATCH='{ matched = 0; for (i = 1; i < NF; i++) if ($i ~ /^(ssh-|ecdsa-|sk-)/) { matched = ($(i + 1) == key); break } }'
    VITO_KEYS_TMP=$(mktemp ~/.ssh/authorized_keys.XXXXXX) || { echo 'VITO_SSH_ERROR' && exit 1; }
    if ! awk -v key={!! escapeshellarg($material) !!} "$VITO_KEY_MATCH"' !matched { print }' ~/.ssh/authorized_keys > "$VITO_KEYS_TMP"; then
        rm -f "$VITO_KEYS_TMP"
        echo 'VITO_SSH_ERROR' && exit 1
    fi
    if ! awk -v key={!! escapeshellarg($material) !!} "$VITO_KEY_MATCH"' matched { found = 1 } END { exit found }' "$VITO_KEYS_TMP"; then
        rm -f "$VITO_KEYS_TMP"
        echo 'VITO_SSH_ERROR' && exit 1
    fi
    if ! mv -f "$VITO_KEYS_TMP" ~/.ssh/authorized_keys; then
        rm -f "$VITO_KEYS_TMP"
        echo 'VITO_SSH_ERROR' && exit 1
    fi
fi
