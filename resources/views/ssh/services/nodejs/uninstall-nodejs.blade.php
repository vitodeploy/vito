export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"

@if ($default)
if ! nvm unalias default; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
if ! nvm deactivate; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
@endif

if ! nvm uninstall {{ $version }}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi
