sudo service vito-agent stop

sudo systemctl disable vito-agent

sudo rm -f /usr/local/bin/vito-agent

sudo rm -f /etc/systemd/system/vito-agent.service

sudo rm -rf /etc/vito-agent

sudo systemctl daemon-reload

echo "Vito Agent uninstalled successfully"
