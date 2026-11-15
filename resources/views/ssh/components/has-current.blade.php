if [ -d {!! escapeshellarg($component->currentPath()) !!} ]; then
    printf 'VITO_COMPONENT_CURRENT_EXISTS'
fi
