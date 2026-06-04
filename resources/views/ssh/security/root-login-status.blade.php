VALUE=$(sudo sshd -T 2>/dev/null | awk '/^permitrootlogin /{print $2; exit}')
echo "VITO_ROOT_LOGIN:${VALUE:-unknown}"
