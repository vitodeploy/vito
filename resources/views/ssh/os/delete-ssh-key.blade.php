if [ -f ~/.ssh/authorized_keys ]; then
    grep -vF '{!! addslashes(trim($key)) !!}' ~/.ssh/authorized_keys > ~/.ssh/authorized_keys.tmp 2>/dev/null || true
    if [ -f ~/.ssh/authorized_keys.tmp ]; then
        mv ~/.ssh/authorized_keys.tmp ~/.ssh/authorized_keys
    fi
fi
