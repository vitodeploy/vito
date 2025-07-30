echo "Uninstalling Node.js and npm..."

# Remove Node.js and npm binaries
sudo apt-get purge -y nodejs

# Remove NodeSource APT repo config
sudo rm -f /etc/apt/sources.list.d/nodesource.list
sudo rm -f /usr/share/keyrings/nodesource.gpg

# Clean up unused packages and cache
sudo apt-get autoremove -y
sudo apt-get clean

if [ -d "$HOME/.nvm" ]; then
  echo "Found nvm installation. Removing..."
  rm -rf "$HOME/.nvm"

  # Remove nvm init lines from shell configs
  sed -i '/nvm.sh/d' "$HOME/.bashrc" 2>/dev/null || true
  sed -i '/nvm.sh/d' "$HOME/.zshrc" 2>/dev/null || true
  sed -i '/NVM_DIR/d' "$HOME/.bashrc" 2>/dev/null || true
  sed -i '/NVM_DIR/d' "$HOME/.zshrc" 2>/dev/null || true
else
  echo "No nvm installation found."
fi

echo "Cleaning up Node.js and npm binaries from PATH..."

BIN_PATHS=(
  "/usr/local/bin/node"
  "/usr/local/bin/npm"
  "/usr/local/bin/npx"
  "/usr/bin/node"
  "/usr/bin/npm"
  "/usr/bin/npx"
  "$HOME/bin/node"
  "$HOME/bin/npm"
  "$HOME/bin/npx"
)

for bin in "${BIN_PATHS[@]}"; do
  if [ -f "$bin" ]; then
    echo "Error Removing $bin"
    sudo rm -f "$bin"
  fi
done

# Check if node and npm still exist
if command -v node >/dev/null 2>&1 || command -v npm >/dev/null 2>&1; then
  echo "Failed to remove nodejs/npm. The binaries still exist!" && exit 1
else
  echo "Node.js and npm have been successfully uninstalled."
fi

