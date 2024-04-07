arch=$(uname -m)

if [ "$arch" == "x86_64" ]; then
    executable="vitoagent-linux-amd64"
elif [ "$arch" == "i686" ]; then
    executable="vitoagent-linux-amd"
elif [ "$arch" == "armv7l" ]; then
    executable="vitoagent-linux-arm"
elif [ "$arch" == "aarch64" ]; then
    executable="vitoagent-linux-arm64"
else
    executable="vitoagent-linux-amd64"
fi

wget __download_url__/$executable

chmod +x ./$executable

sudo mv ./$executable /usr/local/bin/vito-agent

# create service
export VITO_AGENT_SERVICE="
[Unit]
Description=Vito Agent
After=network.target

[Service]
Type=simple
User=root
ExecStart=/usr/local/bin/vito-agent
Restart=on-failure

[Install]
WantedBy=multi-user.target
"
echo "${VITO_AGENT_SERVICE}" | sudo tee /etc/systemd/system/vito-agent.service

sudo mkdir -p /etc/vito-agent

export VITO_AGENT_CONFIG="
{
    \"url\": \"__config_url__\",
    \"secret\": \"__config_secret__\"
}
"

echo "${VITO_AGENT_CONFIG}" | sudo tee /etc/vito-agent/config.json

sudo systemctl daemon-reload
sudo systemctl enable vito-agent
sudo systemctl start vito-agent

echo "Vito Agent installed successfully"
