if id -u {{ $user }} >/dev/null 2>&1; then
    if id -nG {{ $serverUser }} | tr ' ' '\n' | grep -Fxq '{{ $user }}'; then
        sudo userdel -r -f "{{ $user }}"
        if getent group {{ $user }} >/dev/null; then
            sudo groupdel {{ $user }}
        fi
        echo "User {{ $user }} has been deleted."
    else
        echo "User {{ $user }} was not created by Vito, leaving it in place."
    fi
fi
