if sudo test -f {{ $conf }}.vito.bak; then
    sudo cp {{ $conf }}.vito.bak {{ $conf }}
fi
