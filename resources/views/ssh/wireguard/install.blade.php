export DEBIAN_FRONTEND=noninteractive

sudo apt-get update -y
sudo apt-get install -y wireguard wireguard-tools

if ! command -v wg >/dev/null 2>&1; then
    echo "VITO_SSH_ERROR: wireguard installation failed"
    exit 1
fi
