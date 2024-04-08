if [ -f __path__ ]; then
    if [ -n __lines__ ]; then
        tail -n __lines__ __path__
    else
        cat __path__
    fi
fi
