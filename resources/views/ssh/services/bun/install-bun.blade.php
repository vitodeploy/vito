# Define the Bun version to install (e.g., "1.1", "1.0")
BUN_VERSION={{ $version }}

# Install Bun using the official installer
curl -fsSL https://bun.sh/install | bash -s "bun-v${BUN_VERSION}"

# Move Bun and Bunx binaries to /usr/local/bin for global access
sudo mv ~/.bun/bin/bun /usr/local/bin/
sudo chmod a+x /usr/local/bin/bun
sudo mv ~/.bun/bin/bunx /usr/local/bin/
sudo chmod a+x /usr/local/bin/bunx

# The installation script adds  "~/.bun/bin" to $PATH in "~/.bashrc" automatically, we should remove it
sed -i '/\.bun\/bin/d' "$HOME/.bashrc" 2>/dev/null || true
sed -i '/\.bun\/bin/d' "$HOME/.zshrc" 2>/dev/null || true

# Verify installation
bun --version
