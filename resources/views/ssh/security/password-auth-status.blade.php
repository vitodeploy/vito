VALUE=$(sudo sshd -T 2>/dev/null | awk '/^passwordauthentication /{print $2; exit}')
echo "VITO_PASSWORD_AUTH:${VALUE:-unknown}"
