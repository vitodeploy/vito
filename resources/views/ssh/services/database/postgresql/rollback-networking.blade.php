if [ -f {{ $conf }}.vito.bak ]; then
    sudo cp {{ $conf }}.vito.bak {{ $conf }}
fi

if [ -f {{ $hba }}.vito.bak ]; then
    sudo cp {{ $hba }}.vito.bak {{ $hba }}
fi

sudo rm -f {{ $dropIn }}
