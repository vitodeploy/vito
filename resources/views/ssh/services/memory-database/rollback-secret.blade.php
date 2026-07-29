if sudo test -f {{ $include }}.vito.bak; then
    sudo cp {{ $include }}.vito.bak {{ $include }}
else
    sudo rm -f {{ $include }}
fi
