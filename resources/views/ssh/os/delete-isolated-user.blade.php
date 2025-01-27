sudo gpasswd -d {{ $serverUser }} {{ $user }}
sudo userdel -r "{{ $user }}"
echo "User {{ $user }} has been deleted."
