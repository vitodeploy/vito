#!/bin/bash

sudo apt update && sudo apt upgrade -y

echo "Downloading Stalwart..."
curl --proto '=https' --tlsv1.2 -sSf https://get.stalw.art/install.sh -o install_stalwart.sh

# Change hostname in install script
sudo perl -pi -e 's/local _host=\$\(hostname\)/local _host=__domain__/g' install_stalwart.sh

sudo sh install_stalwart.sh

echo "Changing hostname..."
sleep 10

sudo perl -pi -e 's/lookup.default.hostname = ".*"/lookup.default.hostname = "__domain__"/' /opt/stalwart-mail/etc/config.toml
sudo service stalwart-mail restart

echo "Service installed."
