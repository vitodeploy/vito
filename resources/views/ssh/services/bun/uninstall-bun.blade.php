echo "Uninstalling Bun..."

# Remove Bun installation directory
rm -rf "$HOME/.bun"

# Remove Bun init lines from shell configs
sed -i '/\.bun/d' "$HOME/.bashrc" 2>/dev/null || true
sed -i '/\.bun/d' "$HOME/.zshrc" 2>/dev/null || true
sed -i '/BUN_INSTALL/d' "$HOME/.bashrc" 2>/dev/null || true
sed -i '/BUN_INSTALL/d' "$HOME/.zshrc" 2>/dev/null || true

echo "Bun uninstalled successfully."
