if id -u {!! escapeshellarg($user) !!} >/dev/null 2>&1; then
    sudo -H -u {!! escapeshellarg($user) !!} bash <<'VITO_COMPONENT_CLEANUP'
set -e
rm -f -- "$HOME/.ssh/{{ $key }}" "$HOME/.ssh/{{ $key }}.pub"
if [ -f "$HOME/.ssh/config" ]; then
    (
        flock -w 60 9 || exit 1
        awk -v suffix='-{{ $key }}' '
            /^Host[[:space:]]/ { skip = (NF == 2 && substr($2, length($2) - length(suffix) + 1) == suffix) }
            !skip { print }
        ' "$HOME/.ssh/config" > "$HOME/.ssh/config.vito.tmp"
        mv -- "$HOME/.ssh/config.vito.tmp" "$HOME/.ssh/config"
        chmod 600 "$HOME/.ssh/config"
    ) 9>"$HOME/.ssh/config.vito.lock"
fi
VITO_COMPONENT_CLEANUP
fi
