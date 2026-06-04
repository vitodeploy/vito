if [ -f {{ $path }} ]; then
    sudo rm -f {{ $path }}
    echo "Removed basic auth file {{ $path }}"
else
    echo "Basic auth file {{ $path }} not present, nothing to remove"
fi
