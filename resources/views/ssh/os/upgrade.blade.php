set -o pipefail 2>/dev/null || true
export LC_ALL=C
UPGRADE_LOG=$(mktemp)
trap 'rm -f "$UPGRADE_LOG"' EXIT
sudo DEBIAN_FRONTEND=noninteractive NEEDRESTART_MODE=a apt-get clean
sudo DEBIAN_FRONTEND=noninteractive NEEDRESTART_MODE=a apt-get update -o Acquire::AllowReleaseInfoChange::Label=true
sudo DEBIAN_FRONTEND=noninteractive NEEDRESTART_MODE=a apt-get upgrade -y | tee "$UPGRADE_LOG"
sudo DEBIAN_FRONTEND=noninteractive NEEDRESTART_MODE=a apt-get -o Dpkg::Options::="--force-confdef" -o Dpkg::Options::="--force-confold" upgrade -y
sudo DEBIAN_FRONTEND=noninteractive NEEDRESTART_MODE=a apt-get autoremove -y
echo "Packages upgraded:$(grep -oE '^[0-9]+ upgraded' "$UPGRADE_LOG" | grep -oE '^[0-9]+' | head -1 || echo 0)"
echo "Reboot required:$([ -f /var/run/reboot-required ] && echo 1 || echo 0)"
