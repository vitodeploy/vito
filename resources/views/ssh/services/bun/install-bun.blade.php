# Define the Bun version to install (e.g., "1.1", "1.0")
BUN_VERSION={{ $version }}

# Install Bun using the official installer
curl -fsSL https://bun.sh/install | bash -s "bun-v${BUN_VERSION}"

# Add Bun to PATH for all users
export BUN_INSTALL="$HOME/.bun"
export PATH="$BUN_INSTALL/bin:$PATH"

# Verify installation
bun --version
