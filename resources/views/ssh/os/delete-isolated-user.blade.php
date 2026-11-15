VITO_SERVER_USER_GROUPS=$(id -nG {{ $serverUser }})
if printf '%s\n' $VITO_SERVER_USER_GROUPS | grep -Fxq '{{ $user }}'; then
    if id -u {{ $user }} >/dev/null 2>&1; then
        sudo userdel -r -f "{{ $user }}"
    fi
    if [ -e /home/{{ $user }} ]; then
        sudo rm -rf /home/{{ $user }}
    fi
    if getent group {{ $user }} | cut -d: -f4 | tr ',' '\n' | grep -vxF -e {{ $serverUser }} -e '' | grep -q .; then
        sudo gpasswd -d {{ $serverUser }} {{ $user }}
    elif getent group {{ $user }} >/dev/null; then
        sudo groupdel {{ $user }}
    fi
    echo "User {{ $user }} has been deleted."
elif id -u {{ $user }} >/dev/null 2>&1; then
    echo "User {{ $user }} was not created by Vito, leaving it in place."
fi
unset VITO_SERVER_USER_GROUPS
