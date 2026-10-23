if id -u {{ $user }} >/dev/null 2>&1; then
    if id -nG {{ $serverUser }} | tr ' ' '\n' | grep -Fxq '{{ $user }}'; then
        sudo gpasswd -d {{ $serverUser }} {{ $user }}
    fi
    sudo userdel -r -f "{{ $user }}"
fi
echo "User {{ $user }} has been deleted."
