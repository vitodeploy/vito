export PATH=$HOME/.local/share/mise/shims:$PATH

@if (! empty($configDir))
mise use --path {!! escapeshellarg($configDir.'/mise.toml') !!} {!! escapeshellarg($runtime.'@'.$version) !!} --verbose
mise trust {!! escapeshellarg($configDir.'/mise.toml') !!}
@else
mise use -g {{ $runtime . '@' . $version }} --verbose
@endif

ACTIVATION_LINE='export PATH="$HOME/.local/share/mise/shims:$PATH"'
for FILE in "$HOME/.bashrc" "$HOME/.profile"; do
    touch "$FILE"
    if ! grep -Fqx "$ACTIVATION_LINE" "$FILE"; then
        printf '\n%s\n' "$ACTIVATION_LINE" >> "$FILE"
    fi
done

echo "{{ $runtime . '@' . $version }} installed successfully"
