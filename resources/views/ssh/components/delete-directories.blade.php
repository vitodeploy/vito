if id -u {!! escapeshellarg($component->user) !!} >/dev/null 2>&1; then
    sudo -H -u {!! escapeshellarg($component->user) !!} bash <<'VITO_COMPONENT_CLEANUP'
set -e
rm -rf -- {!! escapeshellarg($component->basePath()) !!}
VITO_COMPONENT_CLEANUP
fi
