# Define the Node.js version you want to install (e.g., "20", "18", "22")
NODE_VERSION={{ $version }}

# Update system packages
sudo apt-get update

# Add NodeSource signing key
curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | sudo gpg --dearmor -o /usr/share/keyrings/nodesource.gpg

# Add NodeSource repository for the chosen version
echo "deb [signed-by=/usr/share/keyrings/nodesource.gpg] https://deb.nodesource.com/node_$NODE_VERSION.x nodistro main" | sudo tee /etc/apt/sources.list.d/nodesource.list

# Update and install Node.js and npm
sudo apt-get update
sudo apt-get install -y nodejs

# Show installed versions
node -v
npm -v
