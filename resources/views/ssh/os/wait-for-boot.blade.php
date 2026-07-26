echo "Waiting for cloud-init and apt to finish first-boot tasks..."

if command -v cloud-init >/dev/null 2>&1; then
    __vito_ci_state=$(sudo cloud-init status 2>/dev/null | awk '/^status:/ {print $2; exit}')
    if [ "$__vito_ci_state" = "running" ]; then
        __vito_ci_exit=0
        sudo timeout {{ $timeout }} cloud-init status --wait >/dev/null 2>&1 || __vito_ci_exit=$?
        if [ "$__vito_ci_exit" -eq 124 ]; then
            echo "Timed out after {{ $timeout }}s waiting for cloud-init to finish." >&2
            exit 1
        fi
    fi
fi

if command -v fuser >/dev/null 2>&1; then
    __vito_wait_start=$(date +%s)
    __vito_timeout={{ $timeout }}
    while sudo fuser /var/lib/dpkg/lock-frontend /var/lib/dpkg/lock /var/lib/apt/lists/lock /var/cache/apt/archives/lock >/dev/null 2>&1; do
        if [ "$(( $(date +%s) - __vito_wait_start ))" -ge "$__vito_timeout" ]; then
            echo "Timed out after ${__vito_timeout}s waiting for apt/dpkg locks to be released." >&2
            exit 1
        fi
        echo "apt/dpkg is locked by another process, waiting..."
        sleep 10
    done
else
    echo "fuser not available; unable to wait for apt/dpkg locks (install psmisc to enable)." >&2
fi

echo "System is ready for provisioning."
