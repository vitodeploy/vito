export DEBIAN_FRONTEND=noninteractive

if [ "__isolated_site__" = "true" ]; then
    echo "Isolated site, sudo into __user__"
    if ! sudo su - __user__ -c "rm -rf __path__"; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi

    if ! sudo su - __user__ -c "mkdir __path__"; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi

    if ! sudo su - __user__ -c "chmod -R 755 __path__"; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi
else
    if ! rm -rf __path__; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi

    if ! mkdir __path__; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi

    if ! sudo chmod -R 755 __path__; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi
fi
