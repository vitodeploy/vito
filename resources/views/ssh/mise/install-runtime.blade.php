export PATH=$HOME/.local/share/mise/shims:$PATH

mise use -g {{ $runtime . '@' . $version }} --verbose

echo "{{ $runtime . '@' . $version }} installed successfully"
