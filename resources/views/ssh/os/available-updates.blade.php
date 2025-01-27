sudo DEBIAN_FRONTEND=noninteractive apt-get update

AVAILABLE_UPDATES=$(sudo DEBIAN_FRONTEND=noninteractive apt list --upgradable | wc -l)

echo "Available updates:$AVAILABLE_UPDATES"
