if id -u {{ $user }} >/dev/null 2>&1; then
    if id -nG {{ $serverUser }} | tr ' ' '\n' | grep -Fxq '{{ $user }}'; then
        sudo gpasswd -d {{ $serverUser }} {{ $user }}
        sudo userdel -r -f "{{ $user }}"
        echo "User {{ $user }} has been deleted."
    else
        echo "User {{ $user }} was not created by Vito, leaving it in place."
    fi
fi
