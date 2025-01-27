# Download NVM, if not already downloaded
if [ ! -d "$HOME/.nvm" ]; then
    if ! git clone https://github.com/nvm-sh/nvm.git "$HOME/.nvm"; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi
fi

# Checkout the latest stable version of NVM
if ! git -C "$HOME/.nvm" checkout v0.40.1; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

# Load NVM
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"

# Define the NVM initialization script
NVM_INIT='
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"
'

# List of potential configuration files
CONFIG_FILES=("$HOME/.bash_profile" "$HOME/.bash_login" "$HOME/.profile")

# Flag to track if at least one file exists
FILE_EXISTS=false

# Loop through each configuration file and check if it exists
for config_file in "${CONFIG_FILES[@]}"; do
    if [ -f "$config_file" ]; then
        FILE_EXISTS=true
        # Check if the NVM initialization is already present
        if ! grep -q 'export NVM_DIR="$HOME/.nvm"' "$config_file"; then
            echo "Adding NVM initialization to $config_file"
            echo "$NVM_INIT" >> "$config_file"
        else
            echo "NVM initialization already exists in $config_file"
        fi
    fi
done

# If no file exists, fallback to .profile
if [ "$FILE_EXISTS" = false ]; then
    FALLBACK_FILE="$HOME/.profile"
    echo "No configuration files found. Creating $FALLBACK_FILE and adding NVM initialization."
    echo "$NVM_INIT" >> "$FALLBACK_FILE"
fi

echo "NVM initialization process completed."

# Install NVM if not already installed
if ! command -v nvm > /dev/null 2>&1; then
    if ! bash "$HOME/.nvm/install.sh"; then
        echo 'VITO_SSH_ERROR' && exit 1
    fi
fi

# Install the requested Node.js version
if ! nvm install {{ $version }}; then
    echo 'VITO_SSH_ERROR' && exit 1
fi

echo "Node.js version {{ $version }} installed successfully!"

echo "Node version:" && node -v
echo "NPM version:" && npm -v
echo "NPX version:" && npx -v
