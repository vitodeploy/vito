if id -nG {{ $serverUser }} | tr ' ' '\n' | grep -Fxq '{{ $user }}'; then
    if id -u {{ $user }} >/dev/null 2>&1; then
        sudo userdel -r -f "{{ $user }}"
    fi
    if getent group {{ $user }} >/dev/null; then
        sudo groupdel {{ $user }}
    fi
    echo "User {{ $user }} has been deleted."
elif id -u {{ $user }} >/dev/null 2>&1; then
    echo "User {{ $user }} was not created by Vito, leaving it in place."
fi
