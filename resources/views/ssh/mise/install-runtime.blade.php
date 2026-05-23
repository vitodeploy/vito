export PATH=$HOME/.local/share/mise/shims:$PATH

mise use -g {{ $runtime . '@' . $version }} --verbose

# Make the mise shims available to interactive SSH sessions for this user
# so commands like `node`/`bun`/`pnpm`/`yarn` work from a plain shell, not
# only from Vito's PATH-wrapped commands. Idempotent: only appended once.
ACTIVATION_LINE='export PATH="$HOME/.local/share/mise/shims:$PATH"'
for FILE in "$HOME/.bashrc" "$HOME/.profile"; do
    touch "$FILE"
    if ! grep -Fqx "$ACTIVATION_LINE" "$FILE"; then
        printf '\n%s\n' "$ACTIVATION_LINE" >> "$FILE"
    fi
done

echo "{{ $runtime . '@' . $version }} installed successfully"
