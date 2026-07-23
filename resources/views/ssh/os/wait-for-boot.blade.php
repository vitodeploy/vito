echo "Waiting for cloud-init and apt to finish first-boot tasks..."

if command -v cloud-init >/dev/null 2>&1; then
    sudo cloud-init status --wait >/dev/null 2>&1 || true
fi

if command -v fuser >/dev/null 2>&1; then
    __vito_wait_start=$(date +%s)
    __vito_timeout={{ $timeout }}
    while sudo fuser /var/lib/dpkg/lock-frontend /var/lib/dpkg/lock /var/lib/apt/lists/lock /var/cache/apt/archives/lock >/dev/null 2>&1; do
        if [ "$(( $(date +%s) - __vito_wait_start ))" -ge "$__vito_timeout" ]; then
            echo "Timed out after ${__vito_timeout}s waiting for apt/dpkg locks; continuing."
            break
        fi
        echo "apt/dpkg is locked by another process, waiting..."
        sleep 10
    done
fi

echo "System is ready for provisioning."
