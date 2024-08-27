#!/bin/bash

sudo systemctl stop stalwart-mail
sudo systemctl disable stalwart-mail
sudo rm -Rf /opt/stalwart-mail

echo "Starwalt uninstalled."
