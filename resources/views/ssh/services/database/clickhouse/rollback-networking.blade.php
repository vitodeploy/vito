if sudo test -s {{ $dropIn }}.vito.bak; then
    sudo cp {{ $dropIn }}.vito.bak {{ $dropIn }}
else
    sudo rm -f {{ $dropIn }}
fi
