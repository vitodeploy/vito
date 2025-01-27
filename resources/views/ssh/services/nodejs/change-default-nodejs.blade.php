export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"

if ! nvm alias default {{ $version }}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

if ! nvm use default; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "Default Node.js is now:"
node -v
