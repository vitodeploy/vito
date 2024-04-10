if [ -f __path__ ]; then
    if [ -n __lines__ ]; then
        sudo tail -n __lines__ __path__
    else
        sudo cat __path__
    fi
fi
