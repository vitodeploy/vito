export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"

if ! nvm uninstall __version__; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
