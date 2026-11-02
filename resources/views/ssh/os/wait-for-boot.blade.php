echo "Waiting for cloud-init and apt to finish first-boot tasks..."

@if($isDebian)
if ! command -v sudo >/dev/null 2>&1; then
    if [ "$(id -u)" -ne 0 ]; then
        echo "VITO_SSH_ERROR: sudo is not installed and this user is not root, so Vito cannot install it." >&2
        exit 1
    fi
    sudo() { env "$@"; }
fi

@endif
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

@if($isDebian)
until sudo DEBIAN_FRONTEND=noninteractive apt-get update; do
    if [ "${__vito_apt_attempts:-0}" -ge 30 ]; then
        echo "VITO_SSH_ERROR: apt-get update kept failing." >&2
        exit 1
    fi
    __vito_apt_attempts=$(( ${__vito_apt_attempts:-0} + 1 ))
    sleep 10
done
sudo DEBIAN_FRONTEND=noninteractive apt-get -o DPkg::Lock::Timeout={{ $timeout }} install -y sudo psmisc openssl

if ! sudo grep -q '^PATH=' /etc/environment 2>/dev/null; then
    echo 'PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"' | sudo tee -a /etc/environment > /dev/null
fi

@endif
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
