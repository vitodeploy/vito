sudo DEBIAN_FRONTEND=noninteractive apt-get update

AVAILABLE_UPDATES=$(sudo DEBIAN_FRONTEND=noninteractive apt list --upgradable 2>/dev/null | tail -n +2 | wc -l)

echo "Available updates:$AVAILABLE_UPDATES"
